<?php
declare(strict_types=1);

use App\Service\AdminService;

$app=require dirname(__DIR__,2).'/bootstrap/admin.php';
$service=new AdminService($app['pdo']);
$metrics=$service->dashboard();
$adminActivity=$service->recentAdminActivity();
$loyaltyActivity=$service->recentLoyaltyActivity();
require APP_ROOT.'/templates/admin/_layout.php';
admin_page_start('Administrator overview','overview');
$cards=[
    ['Registered customers',$metrics['customers_total']??0],
    ['Active customers',$metrics['customers_active']??0],
    ['Products',$metrics['products_total']??0],
    ['Generated codes',$metrics['codes_total']??0],
    ['Successfully claimed',$metrics['codes_claimed']??0],
    ['Points awarded',$metrics['points_awarded']??0],
    ['Available rewards',$metrics['rewards_available']??0],
    ['Pending redemptions',$metrics['redemptions_pending']??0],
    ['Active batches',$metrics['batches_active']??0],
    ['Claims today',$metrics['claims_today']??0],
    ['Points spent',$metrics['points_spent']??0],
    ['Open fraud events',$metrics['fraud_open']??0],
];
?>
<section class="admin-stats" aria-label="System overview">
    <?php foreach ($cards as [$label,$value]): ?><article class="admin-stat"><span><?= e($label) ?></span><strong><?= e(number_format((int)$value)) ?></strong></article><?php endforeach; ?>
</section>
<div class="admin-columns">
    <section class="panel"><div class="section-heading"><h2>Recent administrator activity</h2><a href="<?= e(url('/admin/audit.php')) ?>">View audit log</a></div>
        <?php if ($adminActivity===[]): ?><p class="empty-state">No administrative actions have been recorded yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Action</th><th>Actor</th><th>When (UTC)</th></tr></thead><tbody>
        <?php foreach ($adminActivity as $row): ?><tr><td><?= e($row['action']) ?><small><?= e($row['entity_type']) ?> #<?= e($row['entity_id']) ?></small></td><td><?= e($row['actor_email']) ?></td><td><?= e($row['created_at']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <section class="panel"><div class="section-heading"><h2>Recent loyalty activity</h2><a href="<?= e(url('/admin/reports.php')) ?>">View reports</a></div>
        <?php if ($loyaltyActivity===[]): ?><p class="empty-state">No points activity has been recorded yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Customer</th><th>Activity</th><th>Points</th></tr></thead><tbody>
        <?php foreach ($loyaltyActivity as $row): ?><tr><td><?= e($row['full_name']?:$row['email']) ?><small><?= e($row['email']) ?></small></td><td><?= e($row['entry_type']) ?><small><?= e($row['reference_label']) ?></small></td><td class="<?= (int)$row['points_delta']>=0?'points-positive':'points-negative' ?>"><?= (int)$row['points_delta']>0?'+':'' ?><?= e($row['points_delta']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
</div>
<?php admin_page_end(); ?>
