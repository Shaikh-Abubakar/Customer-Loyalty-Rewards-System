<?php
declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/bootstrap/autoload.php';
use App\Support\CodeFormat;
use App\Support\Hmac;
use App\Support\Logger;
use App\Support\RateLimitGuard;
$checks=0;$failures=0;
$check=static function(string $name,bool $ok)use(&$checks,&$failures):void{$checks++;echo($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failures++;};
$read=static function(string $path)use($root):string{$s=file_get_contents($root.'/'.$path);if(!is_string($s))throw new RuntimeException('Unable to read '.$path);return $s;};

$lock=RateLimitGuard::lockName('claim-ip','192.0.2.4');
$check('Rate-limit lock names are deterministic, bounded and opaque', $lock===RateLimitGuard::lockName('claim-ip','192.0.2.4')&&strlen($lock)<=64&&!str_contains($lock,'192.0.2.4'));
$guard=$read('src/Support/RateLimitGuard.php');
$check('Rate-limit guard uses parameterized GET_LOCK with timeout',str_contains($guard,'SELECT GET_LOCK(:lock_name, 5)'));
$check('Rate-limit locks are sorted and released in reverse order',str_contains($guard,'sort($names, SORT_STRING)')&&str_contains($guard,'array_reverse($acquired)'));
$check('Rate-limit guard fails closed when the coordinator cannot be acquired',str_contains($guard,'The rate-limit coordinator is unavailable'));

$schema=$read('database/schema.sql');$migration=$read('database/stage7_security_upgrade.sql');$regRepo=$read('src/Repository/RegistrationAttemptRepository.php');$auth=$read('src/Service/AuthService.php');$regPage=$read('public/register.php');
$check('Fresh schema and upgrade migration include registration_attempts',str_contains($schema,'CREATE TABLE registration_attempts')&&str_contains($migration,'CREATE TABLE IF NOT EXISTS registration_attempts'));
$check('Registration attempt records contain no email, name, password or code fields',str_contains($regRepo,'ip_address')&&str_contains($regRepo,'outcome')&&!preg_match('/email|password|full_name|form_data|plaintext_code/i',$regRepo));
$check('Registration is persistently rate limited at the configured 5 per 15-minute default',str_contains($auth,'registration_attempts\'] ?? 5')&&str_contains($auth,'registration_window_seconds\'] ?? 900')&&str_contains($read('config/app.php'),'REGISTRATION_ATTEMPTS_PER_IP'));
$check('Registration records ordinary and rate-limited attempts, but counts only submitted rows',str_contains($auth,'record($ip, \'submitted\')')&&str_contains($auth,'record($ip, \'rate_limited\')')&&str_contains($regRepo,"outcome = 'submitted'"));
$check('Registration throttling creates a deduplicated fraud-review event without storing form data',str_contains($auth,'existsRecent(\'other\', null, $ip, $window)')&&str_contains($auth,"'Registration attempts reached the configured IP rate limit.'"));
$check('Registration controller passes trusted client IP and displays generic throttle message',str_contains($regPage,'Http::clientIp()')&&str_contains($regPage,'Too many registration attempts'));
$check('Approved A-04 login/claim defaults are unchanged and A-35 is documented',str_contains($read('config/app.php'),"'login_failures' => 5")&&str_contains($read('config/app.php'),"'claim_failures' => 10")&&str_contains($read('ASSUMPTIONS.md'),'| A-35 |'));

$claim=$read('src/Service/ClaimService.php');$batch=$read('src/Service/BatchService.php');
$check('Login counter checks are serialized for both IP and normalized account',str_contains($auth,"'scope' => 'login-ip'")&&str_contains($auth,"'scope' => 'login-account'"));
$check('Claim counter checks are serialized for both customer and IP',str_contains($claim,"'scope' => 'claim-user'")&&str_contains($claim,"'scope' => 'claim-ip'"));
$check('Admin CSV password re-entry shares the login rate-limit scopes',str_contains($batch,"'scope'=>'login-ip'")&&str_contains($batch,"'scope'=>'login-account'"));
$check('Admin export account lock key uses its normalized account email',str_contains($batch,'strtolower((string)$accountForLock[\'email\'])'));
$check('Login rechecks credentials against the password hash read under row lock',str_contains($auth,'lockForLogin($userId)')&&str_contains($auth,'password_verify($password, (string) ($locked[\'password_hash\']'));
$check('Login rejects passwords exceeding bcrypt 72-byte input limit',str_contains($auth,'strlen($password) <= 72')&&str_contains($auth,'oversized-password-rejected'));
$check('Login rehashes outdated password hashes after locked verification',str_contains($auth,'password_needs_rehash(')&&str_contains($auth,'updatePasswordHash('));
$check('Unknown-account login still verifies a fixed dummy password hash',str_contains($auth,'$dummyHash')&&str_contains($auth,'password_verify('));

$session=$read('src/Support/SessionManager.php');$middleware=$read('src/Middleware/AuthMiddleware.php');
$check('Session cookies use strict mode, cookies-only, HttpOnly and SameSite',str_contains($session,"session.use_strict_mode")&&str_contains($session,"session.use_only_cookies")&&str_contains($session,"'httponly' => true")&&str_contains($session,'\'samesite\' => $sameSite'));
$check('Session regeneration checks the native regeneration result',str_contains($session,'if (!session_regenerate_id(true))'));
$check('Login clears pre-authentication state before checked session regeneration',str_contains($auth,'$_SESSION = [];')&&str_contains($auth,'SessionManager::regenerateId()'));
$check('Inactive-session reset uses the checked SessionManager API',str_contains($middleware,'SessionManager::regenerateId()')&&!str_contains($middleware,'session_regenerate_id(true)'));
$helpers=$read('src/Support/helpers.php');
$check('CSRF token uses 32 random bytes and hash_equals',str_contains($helpers,'random_bytes(32)')&&str_contains($helpers,'hash_equals('));
$check('require_csrf itself rejects non-POST methods',str_contains($helpers,"if (!request_method_is('POST'))")&&str_contains($helpers,"header('Allow: POST')"));
$admins=glob($root.'/public/admin/*.php')?:[];$guarded=$admins!==[];$csrf=true;
foreach($admins as $page){$src=file_get_contents($page);if(!is_string($src)||!str_contains($src,'bootstrap/admin.php'))$guarded=false;if(is_string($src)&&preg_match('/request_method_is\s*\(\s*[\'\"]POST[\'\"]\s*\)/',$src)&&!str_contains($src,'require_csrf()'))$csrf=false;}
$check('Every admin route loads server-side admin authorization', $guarded&&str_contains($read('bootstrap/admin.php'),'AuthMiddleware::requireAdmin'));
$check('Admin POST handlers validate CSRF', $csrf);

$headers=$read('src/Support/SecurityHeaders.php');
$check('CSP requires the per-response nonce and disallows unsafe-inline/eval',str_contains($headers,"script-src 'self' 'nonce-")&&!str_contains($headers,'unsafe-inline')&&!str_contains($headers,'unsafe-eval'));
$check('Headers include no-sniff, clickjacking protection, COOP and CORP',str_contains($headers,'X-Content-Type-Options: nosniff')&&str_contains($headers,'X-Frame-Options: DENY')&&str_contains($headers,'Cross-Origin-Opener-Policy: same-origin')&&str_contains($headers,'Cross-Origin-Resource-Policy: same-origin'));
$check('HSTS is conditional on HTTPS so local HTTP remains supported',str_contains($headers,'SessionManager::httpsIsActive()')&&str_contains($headers,'Strict-Transport-Security: max-age=31536000'));
$redacted=Logger::redactSensitive('password=do-not-log csrf_token=private hmac_key=secret code=23456789ABCDEFGH');
$redactedGrouped=Logger::redactSensitive('code=2345-6789-ABCD-EFGH');
$check('Logger masks credential/key values and compact or grouped product codes',!str_contains($redacted,'do-not-log')&&!str_contains($redacted,'private')&&!str_contains($redacted,'secret')&&!str_contains($redacted,'23456789ABCDEFGH')&&str_contains($redacted,'[PRODUCT-CODE]')&&!str_contains($redactedGrouped,'2345-6789-ABCD-EFGH')&&str_contains($redactedGrouped,'[PRODUCT-CODE]'));
$check('Technical logs use daily filenames for the 30-day retention policy',str_contains($read('src/Support/Logger.php'),"'application-' . gmdate('Y-m-d') . '.log'"));

$check('Approved code alphabet is 30 distinct symbols and format remains 16 characters',strlen(CodeFormat::ALPHABET)===30&&count(array_unique(str_split(CodeFormat::ALPHABET)))===30&&CodeFormat::isValid('23456789ABCDEFGH'));
$check('Code normalization accepts only spaces/hyphens as separators and uppercases letters',CodeFormat::normalize('2345-6789 abcd efgh')==='23456789ABCDEFGH');
$check('Code format rejects forbidden characters and incorrect length',!CodeFormat::isValid('23456789ABCDEFGI')&&!CodeFormat::isValid('23456789ABCDEFG'));
$check('Generator uses cryptographic random_int with the approved alphabet',str_contains($batch,'random_int(0,$last)')&&str_contains($batch,"'23456789ABCDEFGHJKMNPQRSTVWXYZ'"));
$check('Code HMAC uses SHA-256 and checks the registered key algorithm',str_contains($read('src/Support/Hmac.php'),"hash_hmac('sha256'")&&str_contains($read('src/Service/CodeVerifierService.php'),"'HMAC-SHA256'"));
$check('HMAC key-check and stored-digest checks use hash_equals',str_contains($read('src/Service/CodeVerifierService.php'),'hash_equals(')&&str_contains($read('src/Repository/CodesRepository.php'),'hash_equals('));
$check('Code repository removes stored HMAC from returned record',str_contains($read('src/Repository/CodesRepository.php'),'unset($row[\'code_hash\'])'));
$check('Claim operation locks customer and batch and conditionally flips unclaimed to claimed',str_contains($claim,'lockActiveCustomer($userId)')&&str_contains($read('src/Repository/CodesRepository.php'),"status = 'unclaimed'"));
$check('Claim credit and success attempt remain inside the claim database transaction',str_contains($claim,'Database::transaction')&&str_contains($claim,'recordClaimCredit(')&&str_contains($claim,'record($userId, $ip, \'success\''));
$check('Unique points-ledger code reference prevents duplicate point credits',str_contains($schema,'UNIQUE KEY uq_points_ledger_claim_code (claim_code_id)'));
$check('Claim attempts never store submitted plaintext code data',!preg_match('/INSERT INTO claim_attempts[^;]*(?:plain|code_text)/i',$read('src/Repository/ClaimAttemptRepository.php')));
$check('Claim rate-limit counts exclude rate_limited outcomes',str_contains($read('src/Repository/ClaimAttemptRepository.php'),"outcome IN ('invalid_format','not_found','already_claimed','batch_inactive','code_voided')"));
$check('Voided batch/code claims create fraud events',str_contains($claim,'voided_batch_code_claimed'));

$ledger=$read('src/Repository/PointsLedgerRepository.php');$redemption=$read('src/Service/RedemptionService.php');
$check('Points balance is derived from SUM(points_delta)',str_contains($ledger,'SUM(points_delta)'));
$check('Points-ledger repository inserts only and contains no update/delete statement',str_contains($ledger,'INSERT INTO points_ledger')&&!preg_match('/(?:UPDATE|DELETE)\s+points_ledger/i',$ledger));
$check('Redemption transaction coordinates debit and stock reservation',str_contains($redemption,'Database::transaction')&&str_contains($redemption,'recordRedemptionDebit'));
$check('Redemption cancellation coordinates a reversal and stock restoration',str_contains($redemption,'reverseRedemptionDebitWithinTransaction')&&str_contains($redemption,'returnOneWithinTransaction'));
$check('Redemption request uses a unique user/idempotency token',str_contains($schema,'UNIQUE KEY uq_redemptions_user_token (user_id, idempotency_token)'));

$dbsec=$read('database/security_hardening.sql');
$check('Database triggers deny UPDATE and DELETE on points_ledger',str_contains($dbsec,'CREATE TRIGGER points_ledger_deny_update')&&str_contains($dbsec,'CREATE TRIGGER points_ledger_deny_delete'));
$check('Database triggers deny UPDATE and DELETE on audit_logs',str_contains($dbsec,'CREATE TRIGGER audit_logs_deny_update')&&str_contains($dbsec,'CREATE TRIGGER audit_logs_deny_delete'));
$check('Runtime app account has only SELECT/INSERT on points_ledger and audit_logs',str_contains($dbsec,'GRANT SELECT, INSERT ON customer_loyalty.points_ledger')&&str_contains($dbsec,'GRANT SELECT, INSERT ON customer_loyalty.audit_logs')&&!preg_match('/GRANT [^;]*(?:UPDATE|DELETE)[^;]* ON customer_loyalty\.(?:points_ledger|audit_logs)/',$dbsec));
$check('Separate maintenance account can delete only the three attempt tables',substr_count($dbsec,'GRANT SELECT, DELETE ON customer_loyalty.')===3&&str_contains($dbsec,"'loyalty_maintenance'@'127.0.0.1'"));
$maintenance=$read('bin/maintain_retention.php');
$check('Retention command is CLI-only and deletes only the three attempt tables',str_contains($maintenance,"PHP_SAPI !== 'cli'")&&str_contains($maintenance,"['login_attempts', 'claim_attempts', 'registration_attempts']")&&!preg_match('/DELETE FROM\s+(?:points_ledger|audit_logs|fraud_events|reconciliation_)/i',$maintenance));
$check('Retention command removes only daily log files beyond 30 days',str_contains($maintenance,'30 * 86400')&&str_contains($maintenance,'application-????-??-??.log'));
$check('CLI maintenance DB settings are separate from runtime DB settings',str_contains($read('config/app.php'),"'database_maintenance' => [")&&str_contains($read('.env.example'),'DB_MAINTENANCE_USER=')&&str_contains($read('.env.example'),'DB_MAINTENANCE_PASSWORD='));

$db=$read('src/Support/Database.php');$adminRepo=$read('src/Repository/AdminRepository.php');$recon=$read('src/Service/ReconciliationService.php');
$check('PDO uses native prepared statements and exception mode',str_contains($db,'PDO::ATTR_EMULATE_PREPARES => false')&&str_contains($db,'PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION'));
$check('Admin search/status filters use prepared statements and bound values',str_contains($adminRepo,'$this->pdo->prepare($sql)')&&str_contains($adminRepo,"' AND b.status=:status'"));
$check('Dynamic admin LIMIT values are clamped before SQL construction',str_contains($adminRepo,'$limit = max(1, min(20, $limit))')&&str_contains($adminRepo,'LIMIT {$limit}'));
$check('Reconciliation dynamic scope names are allowlisted and IDs are bound',str_contains($recon,"['all','customer','batch']")&&str_contains($recon, '\'scope_id\'=>$scopeId'));
$check('URL helper rejects external redirect destinations',str_contains($helpers,'External URLs are not accepted.')&&str_contains($helpers,'Only application-relative URLs are accepted.'));
$check('HTML output helper explicitly escapes quotes and invalid UTF-8',str_contains($helpers,"ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'"));
$check('Client IP uses REMOTE_ADDR only, not untrusted forwarded headers',str_contains($read('src/Support/Http.php'),"['REMOTE_ADDR']")&&!str_contains($read('src/Support/Http.php'),'HTTP_X_FORWARDED_FOR'));
$check('Root web access is denied and public directory indexes are disabled',str_contains($read('.htaccess'),'Require all denied')&&str_contains($read('public/.htaccess'),'Options -Indexes')&&str_contains($read('public/.htaccess'),'Require all denied'));
$check('.env is git-ignored and sample HMAC key value is a placeholder',str_contains($read('.gitignore'),'.env')&&str_contains($read('.env.example'),'REPLACE_WITH_AT_LEAST_32_RANDOM_BYTES_BASE64'));
$check('CSV uses memory-only output, password re-entry and one-time export checks',str_contains($batch,"fopen('php://memory'")&&str_contains($batch,'password_verify(')&&str_contains($batch,'hasExport(')&&!str_contains($batch,'file_put_contents('));
$check('Optional QR fragment can prefill but cannot auto-submit the claim form',str_contains($read('public/claim.php'),'location.hash')&&!preg_match('/form\.submit\s*\(/i',$read('public/claim.php')));

printf("%d checks, %d failures.%s",$checks,$failures,PHP_EOL);
exit($failures===0?0:1);
