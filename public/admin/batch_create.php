<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Service\BatchService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$admin=new AdminService($app['pdo']);$products=$admin->activeProducts();
$error='';$values=['product_id'=>(string)($_POST['product_id']??''),'batch_code'=>(string)($_POST['batch_code']??''),'quantity'=>(string)($_POST['quantity']??'1'),'points_override'=>(string)($_POST['points_override']??'')];
if(request_method_is('POST')){
    require_csrf();
    $productId=filter_var($values['product_id'],FILTER_VALIDATE_INT);$quantity=filter_var($values['quantity'],FILTER_VALIDATE_INT);
    $override=$values['points_override']===''?null:filter_var($values['points_override'],FILTER_VALIDATE_INT);
    try{
        if($productId===false||$quantity===false||($values['points_override']!==''&&$override===false)){throw new InvalidArgumentException('Choose a product and enter valid whole numbers.');}
        $id=(new BatchService($app['pdo']))->create((int)$productId,$values['batch_code'],(int)$quantity,$override===null?null:(int)$override,$adminId,$ip,$correlation);
        Flash::set('success','Draft batch created. Generate and export its codes from the batch details page.');redirect_to('/admin/batch_view.php?id='.$id);
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The batch could not be created.';}
}
admin_page_start('Create batch','batches');
?>
<p><a href="<?= e(url('/admin/batches.php')) ?>">← Back to batches</a></p>
<section class="panel form-panel"><?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($products===[]): ?><p class="empty-state">Create and activate a product before creating a batch.</p><?php else: ?>
<form method="post"><?= csrf_field() ?>
<label for="product_id">Active product</label><select id="product_id" name="product_id" required><option value="">Select a product</option><?php foreach($products as $p): ?><option value="<?= e($p['id']) ?>" <?= $values['product_id']===(string)$p['id']?'selected':'' ?>><?= e($p['name']) ?> (<?= e($p['sku']) ?> · default <?= e($p['default_points']) ?> points)</option><?php endforeach; ?></select>
<label for="batch_code">Batch label</label><input id="batch_code" name="batch_code" type="text" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._-]{0,29}" required value="<?= e($values['batch_code']) ?>"><p class="field-hint">Use a unique, human-readable label (letters, numbers, dot, underscore or hyphen; maximum 30 characters).</p>
<label for="quantity">Number of codes</label><input id="quantity" name="quantity" type="number" min="1" max="1000" required value="<?= e($values['quantity']) ?>"><p class="field-hint">The approved maximum is 1,000 codes per generation request.</p>
<label for="points_override">Points per code override (optional)</label><input id="points_override" name="points_override" type="number" min="1" max="10000" value="<?= e($values['points_override']) ?>"><p class="field-hint">Leave blank to copy the product default. Existing batches never change when a product default is edited.</p>
<div class="form-actions"><button class="button" type="submit">Create draft batch</button><a class="button button--secondary" href="<?= e(url('/admin/batches.php')) ?>">Cancel</a></div>
</form><?php endif; ?></section>
<?php admin_page_end(); ?>
