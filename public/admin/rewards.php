<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Service\RewardService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$service=new RewardService($app['pdo']);$error='';
$editId=isset($_GET['id'])?(int)$_GET['id']:null;
if(request_method_is('POST')){
    require_csrf();$action=(string)($_POST['action']??'');
    try{
        if($action==='save'){
            $id=isset($_POST['id'])&&$_POST['id']!==''?(int)$_POST['id']:null;
            $cost=filter_var($_POST['points_cost']??null,FILTER_VALIDATE_INT);$stock=filter_var($_POST['stock_quantity']??null,FILTER_VALIDATE_INT);
            if($cost===false||($id===null&&$stock===false)){throw new InvalidArgumentException('Enter valid whole-number points and stock values.');}
            if($id===null){$service->createAdmin((string)($_POST['name']??''),trim((string)($_POST['description']??''))?:null,(int)$cost,(int)$stock,$adminId,$ip,$correlation);Flash::set('success','Reward created.');}
            else{$service->updateAdmin($id,(string)($_POST['name']??''),trim((string)($_POST['description']??''))?:null,(int)$cost,$adminId,$ip,$correlation);Flash::set('success','Reward details saved.');}
        }elseif($action==='toggle'){
            $service->setActiveAdmin((int)($_POST['id']??0),($_POST['active']??'')==='1',$adminId,$ip,$correlation);Flash::set('success','Reward availability updated.');
        }elseif($action==='restock'){
            $qty=filter_var($_POST['amount']??null,FILTER_VALIDATE_INT);if($qty===false){throw new InvalidArgumentException('Enter a whole-number restock quantity.');}
            $service->restockAdmin((int)($_POST['id']??0),(int)$qty,$adminId,$ip,$correlation);Flash::set('success','Reward stock increased.');
        }else{throw new InvalidArgumentException('Unknown reward action.');}
        redirect_to('/admin/rewards.php');
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The reward change could not be saved.';}
}
$rows=(new AdminService($app['pdo']))->rewards();$editing=$editId!==null?(new AdminService($app['pdo']))->reward($editId):null;
if($editId!==null&&$editing===null){http_response_code(404);}
admin_page_start('Rewards catalogue','rewards');
?>
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><h2><?= $editing!==null?'Edit reward':'Create reward' ?></h2><form method="post" class="admin-form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($editing['id']??'') ?>">
<label>Reward name<input name="name" maxlength="150" required value="<?= e($editing['name']??'') ?>"></label><label>Points cost<input type="number" name="points_cost" min="1" max="1000000" required value="<?= e($editing['points_cost']??'100') ?>"></label>
<label class="field-span">Description<textarea name="description" rows="3" maxlength="5000"><?= e($editing['description']??'') ?></textarea></label>
<?php if($editing===null): ?><label>Initial stock<input type="number" name="stock_quantity" min="0" max="1000000000" required value="0"></label><?php else: ?><p class="field-span field-hint">Current stock: <?= e($editing['stock_quantity']) ?>. Use the restock action below to add units; stock is never reduced by editing reward details.</p><?php endif; ?>
<div class="field-span form-actions"><button class="button" type="submit">Save reward</button><?php if($editing!==null): ?><a class="button button--secondary" href="<?= e(url('/admin/rewards.php')) ?>">Stop editing</a><?php endif; ?></div></form></section>
<section class="panel"><h2>All rewards</h2><?php if($rows===[]): ?><p class="empty-state">No rewards have been created yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Reward</th><th>Cost</th><th>Available stock</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['name']) ?><small><?= e($r['description']) ?></small></td><td><?= e($r['points_cost']) ?></td><td><?= e($r['stock_quantity']) ?></td><td><span class="status-pill <?= (int)$r['is_active']===1?'status-active':'status-muted' ?>"><?= (int)$r['is_active']===1?'Active':'Inactive' ?></span></td><td class="reward-actions"><a href="<?= e(url('/admin/rewards.php?id='.(int)$r['id'])) ?>">Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e($r['id']) ?>"><input type="hidden" name="active" value="<?= (int)$r['is_active']===1?'0':'1' ?>"><button class="link-button" type="submit"><?= (int)$r['is_active']===1?'Deactivate':'Activate' ?></button></form><form method="post" class="restock-form"><?= csrf_field() ?><input type="hidden" name="action" value="restock"><input type="hidden" name="id" value="<?= e($r['id']) ?>"><label class="visually-hidden" for="restock-<?= e($r['id']) ?>">Units to add</label><input id="restock-<?= e($r['id']) ?>" type="number" name="amount" min="1" max="1000000000" placeholder="Units" required><button class="link-button" type="submit">Restock</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php admin_page_end(); ?>
