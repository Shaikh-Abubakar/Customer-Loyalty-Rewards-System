<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\RedemptionService;
use App\Service\LedgerService;
use App\Service\ProfileService;
use App\Service\RewardService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$pdo = $app['pdo'];
$currentUser = AuthMiddleware::requireCustomer($pdo);
$headerRole = 'customer';
$userId = (int) $currentUser['id'];
$profile = (new ProfileService($pdo))->findForCustomer($userId) ?? ['full_name' => 'Customer'];
$ledger = new LedgerService($pdo);
$summary = $ledger->summary($userId);
$recentActivity = $ledger->recent($userId, 5);
$rewardCatalogue = (new RewardService($pdo))->activeCatalogue();
$availableRewards = array_values(array_filter(
    $rewardCatalogue,
    static fn (array $reward): bool => (int) $reward['stock_quantity'] > 0
));
$recentRedemptions = (new RedemptionService($pdo))->recent($userId, 5);

$pageTitle = 'Your dashboard';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell">
    <section class="dashboard-intro">
        <div>
            <p class="eyebrow">Customer dashboard</p>
            <h1>Welcome, <?= e($profile['full_name']) ?></h1>
            <p class="muted-copy">Your points and reward activity are all in one place.</p>
        </div>
        <a class="button button--primary" href="<?= e(url('/claim.php')) ?>">Claim a product code</a>
    </section>

    <section class="metric-grid" aria-label="Your loyalty summary">
        <article class="metric-card metric-card--featured">
            <span class="metric-label">Current points balance</span>
            <strong class="metric-value"><?= number_format($summary['balance']) ?></strong>
            <span class="metric-footnote">Calculated from your points history</span>
        </article>
        <article class="metric-card">
            <span class="metric-label">Successfully claimed codes</span>
            <strong class="metric-value"><?= number_format($summary['claimed_codes']) ?></strong>
            <span class="metric-footnote">One successful claim per box code</span>
        </article>
        <article class="metric-card">
            <span class="metric-label">Points earned</span>
            <strong class="metric-value"><?= number_format($summary['earned']) ?></strong>
            <span class="metric-footnote">From valid product-code claims</span>
        </article>
        <article class="metric-card">
            <span class="metric-label">Points spent</span>
            <strong class="metric-value"><?= number_format($summary['spent']) ?></strong>
            <span class="metric-footnote">On submitted reward requests</span>
        </article>
    </section>

    <div class="dashboard-grid">
        <section class="panel" aria-labelledby="activity-heading">
            <div class="section-heading">
                <div><p class="eyebrow">Account activity</p><h2 id="activity-heading">Recent points activity</h2></div>
                <a href="<?= e(url('/history.php')) ?>">View history</a>
            </div>
            <?php if ($recentActivity === []): ?>
                <div class="empty-state">
                    <h3>No points activity yet</h3>
                    <p>Claim the unique code inside an eligible product box to receive your first points.</p>
                    <a class="button button--primary" href="<?= e(url('/claim.php')) ?>">Claim your first code</a>
                </div>
            <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($recentActivity as $activity): ?>
                        <li class="activity-item">
                            <div>
                                <strong><?= e($activity['description']) ?></strong>
                                <span class="muted-copy"><?= e(date('M j, Y · g:i a', strtotime((string) $activity['created_at']))) ?></span>
                            </div>
                            <span class="points-change <?= (int) $activity['points_delta'] >= 0 ? 'points-change--positive' : 'points-change--negative' ?>">
                                <?= (int) $activity['points_delta'] > 0 ? '+' : '' ?><?= number_format((int) $activity['points_delta']) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="panel" aria-labelledby="redemptions-heading">
            <div class="section-heading">
                <div><p class="eyebrow">Your requests</p><h2 id="redemptions-heading">Recent redemptions</h2></div>
                <a href="<?= e(url('/redemptions.php')) ?>">View all</a>
            </div>
            <?php if ($recentRedemptions === []): ?>
                <div class="empty-state">
                    <h3>No redemption requests</h3>
                    <p>When you request a reward, its status will appear here.</p>
                    <a href="<?= e(url('/rewards.php')) ?>">Browse rewards</a>
                </div>
            <?php else: ?>
                <ul class="request-list">
                    <?php foreach ($recentRedemptions as $redemption): ?>
                        <li class="request-item">
                            <div>
                                <strong><?= e($redemption['reward_name']) ?></strong>
                                <span class="muted-copy">Request #<?= (int) $redemption['id'] ?> · <?= number_format((int) $redemption['points_cost']) ?> points</span>
                            </div>
                            <span class="status-badge status-badge--<?= e($redemption['status']) ?>"><?= e(ucfirst($redemption['status'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <section class="panel catalogue-preview" aria-labelledby="available-rewards-heading">
        <div class="section-heading">
            <div><p class="eyebrow">Reward catalogue</p><h2 id="available-rewards-heading">Available rewards</h2></div>
            <a href="<?= e(url('/rewards.php')) ?>">Browse catalogue</a>
        </div>
        <?php if ($availableRewards === []): ?>
            <div class="empty-state"><h3>No rewards are currently in stock</h3><p>Please check the catalogue again later.</p></div>
        <?php else: ?>
            <div class="reward-grid">
                <?php foreach (array_slice($availableRewards, 0, 3) as $reward): ?>
                    <article class="reward-card">
                        <h3><?= e($reward['name']) ?></h3>
                        <p><?= e($reward['description'] ?: 'A reward available through the loyalty programme.') ?></p>
                        <div class="reward-card__footer">
                            <strong><?= number_format((int) $reward['points_cost']) ?> points</strong>
                            <span><?= (int) $reward['stock_quantity'] ?> available</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
