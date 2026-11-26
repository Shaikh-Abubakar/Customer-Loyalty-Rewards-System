<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$error='';
if(request_method_is('POST')){
    require_csrf();
    try{
        (new AdminService($app['pdo']))->reviewFraud((int)($_POST['event_id']??0),(string)($_POST['status']??''),(string)($_POST['note']??''),$adminId,$ip,$correlation);
        Flash::set('success','Fraud event review saved. Any follow-up action must use its separate workflow.');redirect_to('/admin/fraud.php');
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The fraud event could not be reviewed.';}
}
$status=(string)($_GET['status']??'open');$severity=(string)($_GET['severity']??'');$type=(string)($_GET['type']??'');$search=trim((string)($_GET['q']??''));
$rows=(new AdminService($app['pdo']))->fraudEvents($status!==''?$status:null,$severity!==''?$severity:null,$search,$type!==''?$type:null);
admin_page_start('Fraud monitoring','fraud');
?>
<p>Review signals raised by login, code-claim and redemption activity. Event details contain internal identifiers only; passwords, session tokens, plaintext codes and HMAC keys are never displayed.</p>
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="admin-filters panel" method="get"><label>Search event, account, IP or batch<input type="search" name="q" value="<?= e($search) ?>"></label><label>Review status<select name="status"><option value="">All</option><?php foreach(['open','reviewed','dismissed','escalated'] as $v): ?><option value="<?= e($v) ?>" <?= $status===$v?'selected':'' ?>><?= e(ucfirst($v)) ?></option><?php endforeach; ?></select></label><label>Event type<select name="type"><option value="">All types</option><?php foreach(['login_bruteforce','repeated_invalid_codes','claim_rate_exceeded','many_accounts_one_ip','voided_batch_code_claimed','redemption_burst','other'] as $v): ?><option value="<?= e($v) ?>" <?= $type===$v?'selected':'' ?>><?= e(str_replace('_',' ',ucfirst($v))) ?></option><?php endforeach; ?></select></label><label>Severity<select name="severity"><option value="">All</option><?php foreach(['high','warning','info'] as $v): ?><option value="<?= e($v) ?>" <?= $severity===$v?'selected':'' ?>><?= e(ucfirst($v)) ?></option><?php endforeach; ?></select></label><button class="button" type="submit">Filter</button></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Event</th><th>Severity</th><th>Customer / IP</th><th>Batch</th><th>Details</th><th>Status</th><th>Review</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['event_type']) ?><small>#<?= e($r['id']) ?> · <?= e($r['created_at']) ?></small></td><td><span class="status-pill severity-<?= e($r['severity']) ?>"><?= e(ucfirst($r['severity'])) ?></span></td><td><?= e($r['customer_email']??'Unknown account') ?><small><?= e($r['ip_address']??'No IP recorded') ?></small></td><td><?= e($r['batch_code']??($r['batch_id']?'Batch #'.$r['batch_id']:'—')) ?></td><td><?= e($r['details']) ?><small><?= $r['code_id']!==null?'Internal code record #'.e($r['code_id']):'' ?></small></td><td><?= e($r['review_status']) ?></td><td><?php if($r['review_status']==='open'): ?><form method="post" class="fraud-review-form"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= e($r['id']) ?>"><label class="visually-hidden" for="fraud-status-<?= e($r['id']) ?>">Review outcome</label><select id="fraud-status-<?= e($r['id']) ?>" name="status"><option value="reviewed">Reviewed</option><option value="dismissed">Dismissed</option><option value="escalated">Escalated</option></select><label class="visually-hidden" for="fraud-note-<?= e($r['id']) ?>">Review note</label><input id="fraud-note-<?= e($r['id']) ?>" name="note" maxlength="500" placeholder="Review note" required><button class="link-button" type="submit">Save</button></form><?php else: ?><?= e($r['reviewed_at']??'—') ?><small><?= e($r['review_note']??'') ?></small><?php endif; ?></td></tr><?php endforeach; ?>
<?php if($rows===[]): ?><tr><td colspan="7" class="empty-state">No fraud events match these filters.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_page_end(); ?>
