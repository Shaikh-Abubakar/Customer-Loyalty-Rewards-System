<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Service\ProductService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$id=isset($_GET['id'])?(int)$_GET['id']:null;
$service=new AdminService($app['pdo']);
$product=$id!==null?$service->product($id):null;
if($id!==null && $product===null){http_response_code(404);$product=null;}
$values=['sku'=>(string)($product['sku']??''),'name'=>(string)($product['name']??''),'description'=>(string)($product['description']??''),'default_points'=>(string)($product['default_points']??'100')];
$error='';
if(request_method_is('POST')){
    require_csrf();
    foreach(['sku','name','description','default_points'] as $field){$values[$field]=is_string($_POST[$field]??null)?trim($_POST[$field]):'';}
    try{
        $points=filter_var($values['default_points'],FILTER_VALIDATE_INT);
        if($points===false){throw new InvalidArgumentException('Enter a whole-number points value.');}
        (new ProductService($app['pdo']))->save($id,$values['sku'],$values['name'],$values['description'],$points,$adminId,$ip,$correlation);
        Flash::set('success','Product saved.');redirect_to('/admin/products.php');
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException?$e->getMessage():'The product could not be saved. Check that the SKU is unique.';}
}
admin_page_start($id===null?'Add product':'Edit product','products');
?>
<p><a href="<?= e(url('/admin/products.php')) ?>">← Back to products</a></p>
<section class="panel form-panel">
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label for="sku">SKU</label><input id="sku" name="sku" maxlength="40" required value="<?= e($values['sku']) ?>" autocomplete="off">
<label for="name">Product name</label><input id="name" name="name" maxlength="150" required value="<?= e($values['name']) ?>">
<label for="description">Description</label><textarea id="description" name="description" rows="4" maxlength="10000"><?= e($values['description']) ?></textarea>
<label for="default_points">Default points per box</label><input id="default_points" name="default_points" type="number" min="1" max="10000" required value="<?= e($values['default_points']) ?>"><p class="field-hint">Changing the default applies only to future batches. Existing batches keep their saved points value.</p>
<div class="form-actions"><button class="button" type="submit">Save product</button><a class="button button--secondary" href="<?= e(url('/admin/products.php')) ?>">Cancel</a></div>
</form></section>
<?php admin_page_end(); ?>
