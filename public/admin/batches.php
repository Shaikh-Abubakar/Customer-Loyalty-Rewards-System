<?php
declare(strict_types=1);

use App\Service\AdminService;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
$search=trim((string)($_GET['q']??''));$status=(string)($_GET['status']??'');
$rows=(new AdminService($app['pdo']))->batches($search,$status!==''?$status:null);
admin_page_start('Production batches','batches');
?>
<div class="admin-toolbar"><p>Track each batch through draft, generated, exported, printed, active and voided states.</p><a class="button" href="<?= e(url('/admin/batch_create.php')) ?>">Create batch</a></div>
<form class="admin-filters panel" method="get"><label>Search batch or product<input type="search" name="q" value="<?= e($search) ?>"></label><label>Status<select name="status"><option value="">All states</option><?php foreach(['draft','generated','exported','printed','active','voided'] as $v): ?><option value="<?= e($v) ?>" <?= $status===$v?'selected':'' ?>><?= e(ucfirst($v)) ?></option><?php endforeach; ?></select></label><button class="button" type="submit">Filter</button></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Batch</th><th>Product</th><th>Quantity</th><th>Claimed</th><th>Points/code</th><th>Status</th><th>Created</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['batch_code']) ?></td><td><?= e($r['product_name']) ?><small><?= e($r['sku']) ?></small></td><td><?= e($r['planned_quantity']) ?></td><td><?= e($r['claimed_count']) ?></td><td><?= e($r['points_per_code']) ?></td><td><span class="status-pill status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?= e($r['created_at']) ?></td><td><a href="<?= e(url('/admin/batch_view.php?id='.(int)$r['id'])) ?>">View</a></td></tr><?php endforeach; ?>
<?php if($rows===[]): ?><tr><td colspan="8" class="empty-state">No batches found.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_page_end(); ?>
