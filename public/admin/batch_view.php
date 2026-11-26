<?php
declare(strict_types=1);

use App\Service\AdminService;
use App\Service\BatchService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
[$adminId,$ip,$correlation]=admin_context($app);
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);$id=$id===false?0:(int)$id;
$service=new AdminService($app['pdo']);$batch=$id>0?$service->batch($id):null;
if($batch===null){http_response_code(404);admin_page_start('Batch not found','batches');echo '<p class="empty-state">This batch could not be found.</p>';admin_page_end();exit;}
$error='';
if(request_method_is('POST')){
    require_csrf();$action=(string)($_POST['action']??'');
    try{
        $batchService=new BatchService($app['pdo']);
        if($action==='printed'){$batchService->markPrinted($id,$adminId,$ip,$correlation);Flash::set('success','Batch marked as printed.');}
        elseif($action==='activate'){$batchService->activate($id,$adminId,$ip,$correlation);Flash::set('success','Batch activated. Codes can now be claimed.');}
        elseif($action==='void'){$batchService->void($id,(string)($_POST['reason']??''),$adminId,$ip,$correlation);Flash::set('success','Batch voided. Already-awarded points were not changed.');}
        else {throw new InvalidArgumentException('Unknown batch action.');}
        redirect_to('/admin/batch_view.php?id='.$id);
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The batch action could not be completed.';$batch=$service->batch($id)??$batch;}
}
admin_page_start('Batch details','batches');
?>
<p><a href="<?= e(url('/admin/batches.php')) ?>">← Back to batches</a></p>
<?php if($error!==''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><div class="detail-grid"><div><span>Batch code</span><strong><?= e($batch['batch_code']) ?></strong></div><div><span>Product</span><strong><?= e($batch['product_name']) ?> (<?= e($batch['sku']) ?>)</strong></div><div><span>State</span><strong><span class="status-pill status-<?= e($batch['status']) ?>"><?= e(ucfirst($batch['status'])) ?></span></strong></div><div><span>Planned quantity</span><strong><?= e($batch['planned_quantity']) ?></strong></div><div><span>Generated codes</span><strong><?= e($batch['generated_count']??'Not generated') ?></strong></div><div><span>Points per code</span><strong><?= e($batch['points_per_code']) ?></strong></div><div><span>Claimed</span><strong><?= e($batch['claimed_count']) ?></strong></div><div><span>Unclaimed</span><strong><?= e($batch['unclaimed_count']) ?></strong></div><div><span>Voided codes</span><strong><?= e($batch['voided_count']) ?></strong></div></div>
<div class="timeline-list"><p>Created: <?= e($batch['created_at']) ?></p><p>Generated: <?= e($batch['generated_at']??'—') ?></p><p>Exported: <?= e($batch['exported_at']??'—') ?> (export record: <?= e($batch['export_status']??'none') ?>)</p><p>Printed: <?= e($batch['printed_at']??'—') ?></p><p>Activated: <?= e($batch['activated_at']??'—') ?></p><p>Voided: <?= e($batch['voided_at']??'—') ?></p></div>
<?php if($batch['status']==='draft'): ?><div class="action-panel"><h2>Generate and export codes</h2><p>Generation and the single CSV export occur in one protected request. The code text is not saved on the server and cannot be downloaded again.</p><form method="post" action="<?= e(url('/admin/batch_generate_export.php')) ?>"><?= csrf_field() ?><input type="hidden" name="batch_id" value="<?= e($batch['id']) ?>"><label for="confirm-password">Re-enter administrator password</label><input id="confirm-password" type="password" name="password" required autocomplete="current-password"><button class="button" type="submit">Generate and download CSV</button></form></div><?php endif; ?>
<?php if($batch['status']==='exported' && $batch['export_status']==='recorded'): ?><form method="post" class="inline-action"><?= csrf_field() ?><input type="hidden" name="action" value="printed"><button class="button" type="submit">Mark as printed</button></form><?php endif; ?>
<?php if(in_array($batch['status'],['generated','exported'],true) && $batch['export_status']!=='recorded'): ?><p class="notice notice--warning">Code generation or CSV delivery did not complete successfully. This batch cannot be exported again; void it and create a replacement batch.</p><?php endif; ?>
<?php if($batch['status']==='printed'): ?><form method="post" class="inline-action"><?= csrf_field() ?><input type="hidden" name="action" value="activate"><button class="button" type="submit">Activate batch</button><p class="field-hint">Activate only after packing and release checks are complete.</p></form><?php endif; ?>
<?php if($batch['status']!=='voided'): ?><div class="action-panel action-panel--danger"><h2>Void batch</h2><p>Voiding prevents all remaining unclaimed codes in this batch from being used. Previously claimed points are not automatically reversed.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="void"><label for="void-reason">Reason</label><textarea id="void-reason" name="reason" maxlength="500" rows="3" required></textarea><button class="button button--danger" type="submit">Void this batch</button></form></div><?php endif; ?>
<?php if($batch['status']==='voided'): ?><p class="notice">This batch is permanently voided. Create a new batch for replacement codes.</p><?php endif; ?></section>
<?php admin_page_end(); ?>
