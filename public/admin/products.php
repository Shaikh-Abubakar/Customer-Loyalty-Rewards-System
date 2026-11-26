<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Service\ProductService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
if (request_method_is('POST')) {
    require_csrf();
    try {
        $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
        (new ProductService($app['pdo']))->setActive((int)$id,($_POST['active']??'')==='1',$adminId,$ip,$correlation);
        Flash::set('success','Product availability updated.');
    } catch (Throwable $e) { Flash::set('error','The product could not be updated. Check the record and try again.'); }
    redirect_to('/admin/products.php');
}
$search=trim((string)($_GET['q']??''));
$status=(string)($_GET['status']??'');
$rows=(new AdminService($app['pdo']))->products($search,$status!==''?$status:null);
admin_page_start('Products','products');
?>
<div class="admin-toolbar"><p>Create and maintain the factory product catalogue. Products with history are deactivated rather than deleted.</p><a class="button" href="<?= e(url('/admin/product_edit.php')) ?>">Add product</a></div>
<form class="admin-filters panel" method="get"><label>Search SKU or product<input type="search" name="q" value="<?= e($search) ?>" maxlength="150"></label><label>Status<select name="status"><option value="">All</option><option value="active" <?= $status==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option></select></label><button class="button" type="submit">Filter</button></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Product</th><th>SKU</th><th>Default points</th><th>Batches</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['name']) ?><small><?= e($r['description']) ?></small></td><td><?= e($r['sku']) ?></td><td><?= e($r['default_points']) ?></td><td><?= e($r['batch_count']) ?></td><td><span class="status-pill <?= (int)$r['is_active']===1?'status-active':'status-muted' ?>"><?= (int)$r['is_active']===1?'Active':'Inactive' ?></span></td><td class="action-cell"><a href="<?= e(url('/admin/product_edit.php?id='.(int)$r['id'])) ?>">Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>"><input type="hidden" name="active" value="<?= (int)$r['is_active']===1?'0':'1' ?>"><button class="link-button" type="submit"><?= (int)$r['is_active']===1?'Deactivate':'Activate' ?></button></form></td></tr><?php endforeach; ?>
<?php if($rows===[]): ?><tr><td colspan="6" class="empty-state">No products match this search.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php admin_page_end(); ?>
