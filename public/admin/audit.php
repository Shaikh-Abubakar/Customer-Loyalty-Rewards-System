<?php
declare(strict_types=1);

use App\Service\AdminService;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
require_once APP_ROOT.'/templates/admin/_layout.php';
$action=trim((string)($_GET['action']??''));$entity=trim((string)($_GET['entity']??''));$search=trim((string)($_GET['q']??''));
$rows=(new AdminService($app['pdo']))->auditLogs($action,$entity,$search);
admin_page_start('Audit log','audit');
?>
<p>Administrative audit records are append-only. This screen is read-only; no edit or delete operation is available.</p>
<form class="admin-filters panel" method="get"><label>Action<input type="text" name="action" maxlength="60" value="<?= e($action) ?>"></label><label>Entity type<input type="text" name="entity" maxlength="40" value="<?= e($entity) ?>"></label><label>Search actor, reason or entity ID<input type="search" name="q" value="<?= e($search) ?>"></label><button class="button" type="submit">Search log</button></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>When (UTC)</th><th>Actor</th><th>Action</th><th>Entity</th><th>Reason / details</th><th>Request</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['created_at']) ?></td><td><?= e($r['actor_email']??'Initial setup / system') ?></td><td><?= e($r['action']) ?></td><td><?= e($r['entity_type']) ?><?= $r['entity_id']!==null?' #'.e($r['entity_id']):'' ?></td><td><?= e($r['reason']??'—') ?><small><?= e($r['details']??'') ?></small></td><td><?= e($r['ip_address']??'—') ?><small><?= e($r['correlation_id']??'') ?></small></td></tr><?php endforeach; ?>
<?php if($rows===[]): ?><tr><td colspan="6" class="empty-state">No audit records match this search.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_page_end(); ?>
