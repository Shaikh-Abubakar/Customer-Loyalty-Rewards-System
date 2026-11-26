<?php
declare(strict_types=1);

use App\Service\BatchService;
use App\Support\Flash;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
require_method('POST');
require_csrf();
$batchId=filter_var($_POST['batch_id']??null,FILTER_VALIDATE_INT);
$password=is_string($_POST['password']??null)?$_POST['password']:'';
if($batchId===false || (int)$batchId<1 || $password===''){
    Flash::set('error','The export request could not be verified.');redirect_to('/admin/batches.php');
}
try{
    [$adminId,$ip,$correlation]=admin_context($app);
    $result=(new BatchService($app['pdo']))->generateAndExport((int)$batchId,$password,$adminId,$ip,$correlation);
    unset($password);
    if(session_status()===PHP_SESSION_ACTIVE){session_write_close();}
    ignore_user_abort(true);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.str_replace('"','',(string)$result['filename']).'"');
    header('Content-Length: '.strlen((string)$result['csv']));
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo $result['csv'];
    unset($result['csv']);
    flush();
    if(connection_aborted()===1){(new BatchService($app['pdo']))->markDeliveryFailed((int)$batchId,$adminId,$ip,$correlation);}
    exit;
}catch(Throwable $e){
    unset($password);
    Flash::set('error',$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The code export could not be completed. Check the batch status before retrying.');
    redirect_to('/admin/batch_view.php?id='.(int)$batchId);
}
