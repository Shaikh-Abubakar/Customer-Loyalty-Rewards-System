<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Service\RedemptionService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$error='';
if(request_method_is('POST')){
    require_csrf();$action=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);
    try{
        $service=new RedemptionService($app['pdo']);
        if($action==='fulfil'){$service->fulfil($id,$adminId,(string)($_POST['note']??''),$ip,$correlation);Flash::set('success','Redemption marked fulfilled.');}
        elseif($action==='cancel'){$service->cancel($id,$adminId,(string)($_POST['reason']??''),$ip,$correlation);Flash::set('success','Redemption cancelled; points and stock were restored.');}
        else{throw new InvalidArgumentException('Unknown redemption action.');}
        redirect_to('/admin/redemptions.php');
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The redemption could not be processed.';}
}
$search=trim((string)($_GET['q']??''));$status=(string)($_GET['status']??'pending');
$admin=new AdminService($app['pdo']);$rows=$admin->redemptions($search,$status!==''?$status:null);
$detailId=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);$detail=$detailId!==false&&$detailId!==null?$admin->redemption((int)$detailId):null;
admin_page_start('Redemption requests','redemptions');
?>
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="admin-filters panel" method="get"><label>Search customer, reward or reference<input type="search" name="q" value="<?= e($search) ?>"></label><label>Status<select name="status"><option value="">All statuses</option><?php foreach(['pending','fulfilled','cancelled'] as $v): ?><option value="<?= e($v) ?>" <?= $status===$v?'selected':'' ?>><?= e(ucfirst($v)) ?></option><?php endforeach; ?></select></label><button class="button" type="submit">Filter</button></form>
<?php if($detail!==null): ?><section class="panel"><h2>Redemption #<?= e($detail['id']) ?></h2><div class="detail-grid"><div><span>Customer</span><strong><?= e($detail['full_name']?:$detail['email']) ?> (<?= e($detail['email']) ?>)</strong></div><div><span>Reward</span><strong><?= e($detail['reward_name']) ?></strong></div><div><span>Points cost</span><strong><?= e($detail['points_cost']) ?></strong></div><div><span>Status</span><strong><?= e(ucfirst($detail['status'])) ?></strong></div><div><span>Requested</span><strong><?= e($detail['requested_at']) ?></strong></div><div><span>Fulfilment note</span><strong><?= e($detail['fulfilment_note']??'—') ?></strong></div><div><span>Cancellation reason</span><strong><?= e($detail['cancel_reason']??'—') ?></strong></div></div><?php if($detail['status']==='pending'): ?><div class="admin-columns"><form method="post" class="action-panel"><?= csrf_field() ?><input type="hidden" name="action" value="fulfil"><input type="hidden" name="id" value="<?= e($detail['id']) ?>"><h3>Fulfil request</h3><label>Fulfilment note (optional)<input name="note" maxlength="255"></label><button class="button" type="submit">Mark fulfilled</button></form><form method="post" class="action-panel action-panel--danger"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= e($detail['id']) ?>"><h3>Cancel request</h3><label>Reason<textarea name="reason" maxlength="255" required></textarea></label><button class="button button--danger" type="submit">Cancel and refund</button></form></div><?php endif; ?></section><?php elseif($detailId!==false&&$detailId!==null): ?><p class="form-error">That redemption was not found.</p><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Customer</th><th>Reward</th><th>Points</th><th>Status</th><th>Requested (UTC)</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td>#<?= e($r['id']) ?></td><td><?= e($r['full_name']?:$r['email']) ?><small><?= e($r['email']) ?></small></td><td><?= e($r['reward_name']) ?></td><td><?= e($r['points_cost']) ?></td><td><span class="status-pill status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?= e($r['requested_at']) ?></td><td><a href="<?= e(url('/admin/redemptions.php?id='.(int)$r['id'].'&status='.rawurlencode($status))) ?>">View</a></td></tr><?php endforeach; ?>
<?php if($rows===[]): ?><tr><td colspan="7" class="empty-state">No redemption requests match these filters.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_page_end(); ?>
