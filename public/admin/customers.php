<?php
declare(strict_types=1);

use App\Service\AdminService;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
$search=trim((string)($_GET['q']??''));$status=(string)($_GET['status']??'');
$rows=(new AdminService($app['pdo']))->customers($search,$status!==''?$status:null);
admin_page_start('Customers','customers');
?>
<p>Customer records are read-only for support. Account status changes and points corrections require a reason and are audited.</p>
<form class="admin-filters panel" method="get"><label>Search name or email<input type="search" name="q" value="<?= e($search) ?>" maxlength="254"></label><label>Account status<select name="status"><option value="">All</option><?php foreach(['active','suspended','closed'] as $v): ?><option value="<?= e($v) ?>" <?= $status===$v?'selected':'' ?>><?= e(ucfirst($v)) ?></option><?php endforeach; ?></select></label><button class="button" type="submit">Search customers</button></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Customer</th><th>Status</th><th>Points balance</th><th>Claimed codes</th><th>Registered (UTC)</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['full_name']?:'Customer') ?><small><?= e($r['email']) ?><?= $r['phone']!==null?' · '.e($r['phone']):'' ?></small></td><td><span class="status-pill status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?= e(number_format((int)$r['points_balance'])) ?></td><td><?= e($r['claim_count']) ?></td><td><?= e($r['created_at']) ?></td><td><a href="<?= e(url('/admin/customer_view.php?id='.(int)$r['id'])) ?>">View details</a></td></tr><?php endforeach; ?>
<?php if($rows===[]): ?><tr><td colspan="6" class="empty-state">No customers match this search.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_page_end(); ?>
