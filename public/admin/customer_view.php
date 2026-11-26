<?php
declare(strict_types=1);

use App\Service\AccountLifecycleService;
use App\Service\AdminService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);$id=$id===false?0:(int)$id;
$admin=new AdminService($app['pdo']);
$error='';
if(request_method_is('POST')){
    require_csrf();$action=(string)($_POST['action']??'');
    try{
        if($action==='status'){
            (new AccountLifecycleService($app['pdo']))->changeStatus($id,(string)($_POST['status_action']??''),(string)($_POST['reason_category']??''),(string)($_POST['reason']??''),$adminId,$ip,$correlation);
            Flash::set('success','Customer account status updated.');
        }elseif($action==='adjust'){
            $admin->adjustPoints($id,(int)($_POST['delta']??0),(string)($_POST['idempotency_key']??''),(string)($_POST['reason']??''),$adminId,$ip,$correlation);
            Flash::set('success','A new points adjustment was added to the ledger.');
        }elseif($action==='reverse'){
            $admin->reversePoints($id,(int)($_POST['entry_id']??0),(string)($_POST['idempotency_key']??''),(string)($_POST['reason']??''),$adminId,$ip,$correlation);
            Flash::set('success','A compensating ledger entry was added. The original entry was not changed.');
        }else{throw new InvalidArgumentException('Unknown customer action.');}
        redirect_to('/admin/customer_view.php?id='.$id);
    }catch(PDOException $e){
        if((int)($e->errorInfo[1]??0)===1062){$error='This request was already applied. Refresh the page to see the current ledger.';}
        else{$error='The customer action could not be completed. Check the record and try again.';}
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The customer action could not be completed.';}
}
$customer=$id>0?$admin->customer($id,$adminId,$ip,$correlation):null;
if($customer===null){http_response_code(404);admin_page_start('Customer not found','customers');echo '<p class="empty-state">This customer could not be found.</p>';admin_page_end();exit;}
$ledger=$admin->customerLedger($id);$redemptions=$admin->customerRedemptions($id);$events=$admin->customerStatusEvents($id);
$csrf=csrf_token();
admin_page_start('Customer details','customers');
?>
<p><a href="<?= e(url('/admin/customers.php')) ?>">← Back to customers</a></p>
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><div class="detail-grid"><div><span>Customer</span><strong><?= e($customer['full_name']?:'Closed account') ?></strong></div><div><span>Email</span><strong><?= e($customer['email']) ?></strong></div><div><span>Status</span><strong><span class="status-pill status-<?= e($customer['status']) ?>"><?= e(ucfirst($customer['status'])) ?></span></strong></div><div><span>Points balance</span><strong><?= e(number_format((int)$customer['points_balance'])) ?></strong></div><div><span>Phone</span><strong><?= e($customer['phone']??'—') ?></strong></div><div><span>Registered (UTC)</span><strong><?= e($customer['created_at']) ?></strong></div><div><span>Last login (UTC)</span><strong><?= e($customer['last_login_at']??'—') ?></strong></div><div><span>Anonymised</span><strong><?= e($customer['anonymised_at']??'No') ?></strong></div></div></section>
<section class="panel"><h2>Account status action</h2><p>Suspension disables the next customer request. Reactivation of a closure request is only available during the approved 30-day recovery window.</p>
<?php if($customer['status']==='active'||$customer['status']==='suspended'||$customer['status']==='closed'): ?><form method="post" class="admin-form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="status"><label>Action<select name="status_action" required><?php if($customer['status']==='active'): ?><option value="suspend">Suspend customer</option><?php else: ?><option value="reactivate">Reactivate customer</option><?php endif; ?></select></label><label>Reason category<select name="reason_category" required><?php foreach(['fraud_review'=>'Fraud review','abuse'=>'Abuse','customer_request'=>'Customer request','security_concern'=>'Security concern','legal_or_retention'=>'Legal or retention','admin_correction'=>'Administrator correction','other'=>'Other'] as $k=>$v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?></select></label><label class="field-span">Reason<textarea name="reason" rows="2" maxlength="500" required></textarea></label><div class="field-span"><button class="button" type="submit"><?= $customer['status']==='active'?'Suspend account':'Reactivate account' ?></button></div></form><?php else: ?><p>This account has no available status action.</p><?php endif; ?></section>
<section class="panel"><h2>Points adjustment</h2><p>Adjustments append a new ledger entry. Enter a positive number to credit points or a negative number to debit them. The original ledger is never edited.</p><form method="post" class="admin-form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="adjust"><input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(16))) ?>"><label>Points delta<input type="number" name="delta" min="-1000000" max="1000000" required></label><label>Reason<input type="text" name="reason" maxlength="255" required></label><div class="field-span"><button class="button" type="submit">Add adjustment</button></div></form></section>
<section class="panel"><h2>Points ledger</h2><?php if($ledger===[]): ?><p class="empty-state">This customer has no points activity.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Entry</th><th>Reference</th><th>Points</th><th>Reason</th><th>Created (UTC)</th><th>Correction</th></tr></thead><tbody>
<?php foreach($ledger as $entry): ?><tr><td>#<?= e($entry['id']) ?> · <?= e($entry['entry_type']) ?></td><td><?= e($entry['reward_name']??($entry['batch_code']??($entry['redemption_ref']?'Redemption #'.$entry['redemption_ref']:'—'))) ?></td><td><?= (int)$entry['points_delta']>0?'+':'' ?><?= e($entry['points_delta']) ?></td><td><?= e($entry['reason']??'—') ?></td><td><?= e($entry['created_at']) ?></td><td><?php if(in_array($entry['entry_type'],['claim','adjustment'],true)&&$entry['reverses_entry_id']===null): ?><form method="post" class="table-action-form"><?= csrf_field() ?><input type="hidden" name="action" value="reverse"><input type="hidden" name="entry_id" value="<?= e($entry['id']) ?>"><input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(16))) ?>"><input type="text" name="reason" maxlength="255" placeholder="Correction reason" aria-label="Correction reason for ledger entry <?= e($entry['id']) ?>" required><button class="link-button" type="submit">Reverse</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Redemption history</h2><?php if($redemptions===[]): ?><p class="empty-state">No redemption requests.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Reward</th><th>Points cost</th><th>Status</th><th>Requested (UTC)</th></tr></thead><tbody><?php foreach($redemptions as $r): ?><tr><td>#<?= e($r['id']) ?></td><td><?= e($r['reward_name']) ?></td><td><?= e($r['points_cost']) ?></td><td><span class="status-pill status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?= e($r['requested_at']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Account status history</h2><?php if($events===[]): ?><p class="empty-state">No status changes recorded.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Action</th><th>Category</th><th>Reason</th><th>Actor</th><th>When (UTC)</th></tr></thead><tbody><?php foreach($events as $event): ?><tr><td><?= e($event['action']) ?></td><td><?= e($event['reason_category']) ?></td><td><?= e($event['reason_text']) ?></td><td><?= e($event['actor_email']) ?></td><td><?= e($event['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php admin_page_end(); ?>
