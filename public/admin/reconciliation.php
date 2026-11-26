<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Service\ReconciliationService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$error='';
if(request_method_is('POST')){
    require_csrf();
    try{
        $scope=(string)($_POST['scope']??'all');$rawId=$scope==='customer'?trim((string)($_POST['customer_id']??'')):($scope==='batch'?trim((string)($_POST['batch_id']??'')):'');$scopeId=$scope==='all'?null:filter_var($rawId,FILTER_VALIDATE_INT);
        if($scope!=='all'&&($scopeId===false||$scopeId===null)){throw new InvalidArgumentException('Select a customer or batch for the chosen scope.');}
        $result=(new ReconciliationService($app['pdo']))->run($scope,$scopeId===null?null:(int)$scopeId,$adminId,$ip,$correlation);
        Flash::set($result['outcome']==='clean'?'success':'warning','Reconciliation completed: '.$result['outcome'].', '.$result['count'].' finding(s). No data was automatically changed.');redirect_to('/admin/reconciliation.php?run_id='.$result['run_id']);
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'Reconciliation could not be completed. The failed run is recorded where possible.';}
}
$admin=new AdminService($app['pdo']);$runs=$admin->reconciliationRuns();$runId=filter_var($_GET['run_id']??null,FILTER_VALIDATE_INT);$runId=$runId===false?null:($runId===null?null:(int)$runId);$findings=$admin->reconciliationFindings($runId);
$customers=$admin->customers();$batches=$admin->batches();
admin_page_start('Reconciliation','reconciliation');
?>
<p>Manually compare the points ledger with code and redemption records. Reconciliation reports findings only; it never edits the ledger, changes points or repairs stock.</p>
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><h2>Run reconciliation</h2><form method="post" class="admin-form-grid"><?= csrf_field() ?><label>Scope<select name="scope" id="scope-select"><option value="all">All records</option><option value="customer">One customer</option><option value="batch">One batch</option></select></label><label>Customer (used for customer scope)<select name="customer_id"><option value="">Choose a customer</option><?php foreach($customers as $c): ?><option value="<?= e($c['id']) ?>"><?= e($c['full_name']?:$c['email']) ?> — customer #<?= e($c['id']) ?></option><?php endforeach; ?></select></label><label>Batch (used for batch scope)<select name="batch_id"><option value="">Choose a batch</option><?php foreach($batches as $b): ?><option value="<?= e($b['id']) ?>">Batch <?= e($b['batch_code']) ?> — #<?= e($b['id']) ?></option><?php endforeach; ?></select></label><div class="field-span"><button class="button" type="submit">Run checks</button></div></form><p class="field-hint">The selected ID must match the chosen scope. Batch scope runs code-to-claim checks; customer/all scopes run ledger and redemption checks.</p></section>
<?php if($runId!==null): ?><section class="panel"><h2>Findings for run #<?= e($runId) ?></h2><?php if($findings===[]): ?><p class="empty-state">No findings were recorded for this run.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Check</th><th>Customer</th><th>Ledger entry</th><th>Expected</th><th>Actual</th><th>Note</th></tr></thead><tbody><?php foreach($findings as $f): ?><tr><td><?= e($f['check_type']) ?></td><td><?= e($f['customer_email']??($f['user_id']?'Customer #'.$f['user_id']:'—')) ?></td><td><?= e($f['ledger_entry_id']??'—') ?></td><td><?= e($f['expected_value']??'—') ?></td><td><?= e($f['actual_value']??'—') ?></td><td><?= e($f['note']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section><?php endif; ?>
<section class="panel"><h2>Previous runs</h2><?php if($runs===[]): ?><p class="empty-state">No reconciliation runs have been recorded.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Run</th><th>Scope</th><th>Outcome</th><th>Findings</th><th>Started</th><th>Finished</th><th>Operator</th></tr></thead><tbody><?php foreach($runs as $r): ?><tr><td><a href="<?= e(url('/admin/reconciliation.php?run_id='.(int)$r['id'])) ?>">#<?= e($r['id']) ?></a></td><td><?= e($r['scope']) ?><?= $r['scope_ref_id']?' #'.e($r['scope_ref_id']):'' ?></td><td><span class="status-pill status-<?= e($r['outcome']) ?>"><?= e($r['outcome']) ?></span></td><td><?= e($r['discrepancy_count']) ?></td><td><?= e($r['started_at']) ?></td><td><?= e($r['finished_at']??'—') ?></td><td><?= e($r['actor_email']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php admin_page_end(); ?>
