<?php
declare(strict_types=1);

use App\Service\AdminService;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
$today=gmdate('Y-m-d');$monthStart=gmdate('Y-m-01');
$from=(string)($_GET['from']??$monthStart);$to=(string)($_GET['to']??$today);
$validDate=static function(string $d): bool {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/D',$d)!==1) { return false; }
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$d);
    $errors=DateTimeImmutable::getLastErrors();
    return $date!==false && ($errors===false || ($errors['warning_count']===0 && $errors['error_count']===0)) && $date->format('Y-m-d')===$d;
};
$error='';
if(!$validDate($from)||!$validDate($to)||$from>$to||strtotime($to.' UTC')>strtotime($today.' UTC')){$error='Choose valid dates in chronological order, not later than today (UTC).';$from=$monthStart;$to=$today;}
$admin=new AdminService($app['pdo']);$claims=$admin->reportClaimsByBatch($from,$to);$points=$admin->reportPoints($from,$to);$redemptions=$admin->reportRedemptions($from,$to);
admin_page_start('Reports','reports');
?>
<p>Read-only operational reports using the approved date filters. All dates are UTC.</p>
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="admin-filters panel" method="get"><label>From<input type="date" name="from" value="<?= e($from) ?>" required></label><label>To<input type="date" name="to" value="<?= e($to) ?>" required></label><button class="button" type="submit">Apply dates</button></form>
<section class="admin-stats admin-stats--compact"><article class="admin-stat"><span>Points issued</span><strong><?= e(number_format((int)($points['issued']??0))) ?></strong></article><article class="admin-stat"><span>Points spent</span><strong><?= e(number_format((int)($points['spent']??0))) ?></strong></article><article class="admin-stat"><span>Reversal points</span><strong><?= e(number_format((int)($points['reversed']??0))) ?></strong></article><article class="admin-stat"><span>Ledger entries</span><strong><?= e(number_format((int)($points['entries']??0))) ?></strong></article></section>
<section class="panel"><h2>Claims per batch</h2><?php if($claims===[]): ?><p class="empty-state">No batch claim data for this date range.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Batch</th><th>Product</th><th>Claimed codes</th><th>Points issued</th></tr></thead><tbody><?php foreach($claims as $r): ?><tr><td><?= e($r['batch_code']) ?></td><td><?= e($r['product_name']) ?><small><?= e($r['sku']) ?></small></td><td><?= e($r['claimed_codes']) ?></td><td><?= e($r['points_issued']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Redemptions by status</h2><?php if($redemptions===[]): ?><p class="empty-state">No redemption requests in this date range.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Status</th><th>Requests</th><th>Points cost total</th></tr></thead><tbody><?php foreach($redemptions as $r): ?><tr><td><?= e(ucfirst($r['status'])) ?></td><td><?= e($r['total']) ?></td><td><?= e($r['points_total']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php admin_page_end(); ?>
