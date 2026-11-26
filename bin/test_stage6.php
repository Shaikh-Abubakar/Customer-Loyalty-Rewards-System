<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$checks = 0;
$failures = 0;
$check = static function (string $name, bool $condition) use (&$checks, &$failures): void {
    $checks++;
    if ($condition) {
        echo "PASS {$name}\n";
    } else {
        $failures++;
        echo "FAIL {$name}\n";
    }
};
$read = static function (string $relative) use ($root): string {
    $content = file_get_contents($root . '/' . $relative);
    if (!is_string($content)) {
        throw new RuntimeException('Unable to read test input: ' . $relative);
    }
    return $content;
};

$adminPages = glob($root . '/public/admin/*.php') ?: [];
$adminGuarded = $adminPages !== [];
foreach ($adminPages as $page) {
    $source = file_get_contents($page);
    if (!is_string($source) || !str_contains($source, "bootstrap/admin.php")) {
        $adminGuarded = false;
        break;
    }
}
$check('Every administrator route includes the server-side administrator bootstrap', $adminGuarded);
$check('Administrator bootstrap calls the approved requireAdmin middleware', str_contains($read('bootstrap/admin.php'), 'AuthMiddleware::requireAdmin'));
$check('Admin access checks the current database user role and account status', str_contains($read('src/Middleware/AuthMiddleware.php'), "!== 'admin'") && str_contains($read('src/Middleware/AuthMiddleware.php'), "!== 'active'"));
$check('Normal customer login routes administrator accounts to the admin dashboard', str_contains($read('public/login.php'), "redirect_to('/admin/index.php')"));

$adminPostProtected = true;
foreach ($adminPages as $page) {
    $source = file_get_contents($page);
    if (!is_string($source)) { $adminPostProtected = false; break; }
    if (preg_match('/request_method_is\s*\(\s*[\'\"]POST[\'\"]\s*\)/', $source) === 1
        && !str_contains($source, 'require_csrf()')) {
        $adminPostProtected = false;
        break;
    }
}
$check('Administrator POST handlers verify CSRF tokens', $adminPostProtected && str_contains($read('public/admin/batch_generate_export.php'), 'require_csrf()'));
$check('Initial administrator creation is CLI-only', str_contains($read('bin/create_admin.php'), "PHP_SAPI !== 'cli'"));
$check('Initial administrator creation hashes password and audits creation in a transaction', str_contains($read('bin/create_admin.php'), 'password_hash(') && str_contains($read('bin/create_admin.php'), 'Database::transaction') && str_contains($read('bin/create_admin.php'), "'admin_created'"));

$productService = $read('src/Service/ProductService.php');
$check('Products validate points within the approved 1–10,000 range', str_contains($productService, '$points<1 || $points>10000'));
$check('Products are deactivated rather than deleted', str_contains($productService, 'product_deactivated') && !preg_match('/DELETE\s+FROM\s+products/i', $productService));
$check('Product default points are copied at batch creation', str_contains($read('src/Service/BatchService.php'), '(int)$product[\'default_points\']'));

$batchService = $read('src/Service/BatchService.php');
$batchPage = $read('public/admin/batch_create.php');
$check('Administrator enters a required unique human-readable batch label', str_contains($batchPage, 'name="batch_code"') && str_contains($batchService, 'That batch label is already in use'));
$check('Batch labels are constrained to 1–30 safe characters', str_contains($batchService, 'strlen($batchCode)>30') && str_contains($batchService, "[A-Za-z0-9._-]{0,29}"));
$check('Batch quantity is limited to 1–1,000', str_contains($batchService, '$quantity<1 || $quantity>1000'));
$check('Batch points per code are limited to 1–10,000', str_contains($batchService, '$pointsOverride<1 || $pointsOverride>10000'));
$check('Code generation uses the approved 30-character alphabet and random_int', str_contains($batchService, "'23456789ABCDEFGHJKMNPQRSTVWXYZ'") && str_contains($batchService, 'random_int(0,$last)'));
$check('Code hashes use HMAC and validate the registry algorithm', str_contains($batchService, 'Hmac::codeHash(') && str_contains($batchService, "'HMAC-SHA256'"));
$check('Code generation checks duplicates under each usable key', str_contains($batchService, '$this->codes->existsHash($digest,(int)$keyId)'));
$check('CSV uses a memory stream and approved columns', str_contains($batchService, "fopen('php://memory'") && str_contains($batchService, "['batch_code','row_number','product_sku','points','code','claim_url']"));
$check('CSV groups each plaintext code into four-character chunks', str_contains($batchService, 'implode(\'-\',str_split($code,4))'));
$check('Export requires password verification and shared login-attempt throttling', str_contains($batchService, 'password_verify(') && str_contains($batchService, 'countFailuresByIp(') && str_contains($batchService, 'countFailuresByUser('));
$check('Export response is POST-only, no-store and does not write a plaintext file', str_contains($read('public/admin/batch_generate_export.php'), "require_method('POST')") && str_contains($read('public/admin/batch_generate_export.php'), 'Cache-Control: no-store') && !str_contains($batchService, 'file_put_contents('));
$check('Failed generation/export state requires void-and-replace guidance', str_contains($read('public/admin/batch_view.php'), "['generated','exported']") && str_contains($read('public/admin/batch_view.php'), "export_status']!=='recorded") && str_contains($read('public/admin/batch_view.php'), 'void it and create a replacement'));
$check('Ambiguous export-commit failures mark an existing export unusable instead of leaving it recorded', str_contains($batchService, "updateExportStatus(\$batchId,'failed')") && str_contains($batchService, 'No usable plaintext export was returned'));

$accountService = $read('src/Service/AccountLifecycleService.php');
foreach (['fraud_review','abuse','customer_request','security_concern','legal_or_retention','admin_correction','other'] as $reason) {
    if (!str_contains($accountService, "'{$reason}'")) { $reasonListOk = false; break; }
    $reasonListOk = true;
}
$check('Customer status changes use the exact approved A-30 reason categories', $reasonListOk ?? false);
$check('Customer status actions have audit and status-history services', str_contains($accountService, '$this->events->add(') && str_contains($accountService, '$this->audit->record('));
$check('Customer details expose points/claim/redemption history without a points edit form', str_contains($read('public/admin/customer_view.php'), 'Ledger') && !str_contains($read('public/admin/customer_view.php'), 'name="points_delta"'));

$ledgerService = $read('src/Service/LedgerService.php');
$ledgerRepo = $read('src/Repository/PointsLedgerRepository.php');
$check('Points ledger writes use append-only INSERT statements', str_contains($ledgerRepo, 'INSERT INTO points_ledger') && !preg_match('/(?:UPDATE|DELETE)\s+points_ledger/i', $ledgerRepo));
$check('Administrator code never updates or deletes historical points ledger rows', !preg_match('/(?:UPDATE|DELETE)\s+points_ledger/i', $ledgerService . $batchService . $productService . $accountService . $read('src/Service/RewardService.php') . $read('src/Service/RedemptionService.php')));
$check('Redemption cancellation appends reversal, restores stock and changes status transactionally', str_contains($read('src/Service/RedemptionService.php'), 'Database::transaction') && str_contains($read('src/Service/RedemptionService.php'), 'reverseRedemptionDebitWithinTransaction(') && str_contains($read('src/Service/RedemptionService.php'), 'returnOneWithinTransaction(') && str_contains($read('src/Service/RedemptionService.php'), 'markCancelled('));
$check('Fulfilment/cancellation only proceed for pending redemptions', substr_count($read('src/Service/RedemptionService.php'), "['status']!=='pending'") >= 3);
$check('Manual ledger corrections cannot bypass the pending-redemption cancellation workflow', !str_contains($ledgerService, "['claim','redemption','adjustment']") && str_contains($ledgerService, 'reverseRedemptionDebitWithinTransaction(') && !str_contains($read('public/admin/customer_view.php'), "['claim','redemption','adjustment']"));

$fraudRepo = $read('src/Repository/FraudEventRepository.php');
$adminService = $read('src/Service/AdminService.php');
$check('Fraud review accepts only the approved review states', str_contains($adminService, "['reviewed','dismissed','escalated']"));
$check('Fraud review records an audit event', str_contains($adminService, '\'fraud_event_\'.'));
$auditRepo = $read('src/Repository/AuditLogRepository.php');
$check('Audit log repository inserts records without update/delete operations', str_contains($auditRepo, 'INSERT INTO audit_logs') && !preg_match('/(?:UPDATE|DELETE)\s+audit_logs/i', $auditRepo));
$check('Audit browser is read-only', !preg_match('/request_method_is\s*\(\s*[\'\"]POST[\'\"]\s*\)/', $read('public/admin/audit.php')));

$reconciliation = $read('src/Service/ReconciliationService.php');
$check('Manual reconciliation runs the four approved check families', str_contains($reconciliation, 'checkClaimLedger(') && str_contains($reconciliation, 'checkRedemptionDebits(') && str_contains($reconciliation, 'checkReversalLinks(') && str_contains($reconciliation, 'checkCustomerTotals('));
$check('Reconciliation explicitly handles nullable claim-owner and reversal-owner fields', str_contains($reconciliation, 'claimed_by_user_id IS NULL') && str_contains($reconciliation, 'orig.user_id IS NULL'));
$check('Reconciliation records findings without automatic corrective updates', str_contains($reconciliation, 'addFinding(') && !preg_match('/(?:UPDATE|DELETE)\s+(?:points_ledger|codes|redemptions|rewards)/i', $reconciliation));
$check('Reconciliation form uses separate customer and batch scope selectors', str_contains($read('public/admin/reconciliation.php'), 'name="customer_id"') && str_contains($read('public/admin/reconciliation.php'), 'name="batch_id"'));

$dashboard = $read('src/Repository/AdminRepository.php');
$check('Dashboard summary metrics are sourced from SQL aggregate queries', str_contains($dashboard, 'customers_active') && str_contains($dashboard, 'codes_claimed') && str_contains($dashboard, 'redemptions_pending'));
$check('Admin-specific responsive stylesheet exists', is_file($root . '/public/assets/css/admin.css') && filesize($root . '/public/assets/css/admin.css') > 1000);

printf("%d checks, %d failures.\n", $checks, $failures);
exit($failures === 0 ? 0 : 1);
