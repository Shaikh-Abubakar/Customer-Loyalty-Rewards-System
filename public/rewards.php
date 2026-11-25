<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\LedgerService;
use App\Service\RewardService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$currentUser = AuthMiddleware::requireCustomer($app['pdo']);
$headerRole = 'customer';
$userId = (int) $currentUser['id'];
$balance = (new LedgerService($app['pdo']))->balance($userId);
$rewards = (new RewardService($app['pdo']))->activeCatalogue();

$pageTitle = 'Rewards catalogue';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell">
    <div class="page-heading-row">
        <div><p class="eyebrow">Choose your reward</p><h1>Rewards catalogue</h1><p class="muted-copy">Each request is for one unit. Your points are deducted when the request is submitted.</p></div>
        <div class="balance-chip"><span>Your points balance</span><strong><?= number_format($balance) ?> points</strong></div>
    </div>
    <?php if ($rewards === []): ?>
        <section class="panel empty-state"><h2>No rewards are listed right now</h2><p>Please check again later. New rewards will appear here when they are activated.</p></section>
    <?php else: ?>
        <section class="reward-grid reward-grid--catalogue" aria-label="Available rewards">
            <?php foreach ($rewards as $reward): ?>
                <?php
                $cost = (int) $reward['points_cost'];
                $stock = (int) $reward['stock_quantity'];
                $inStock = $stock > 0;
                $affordable = $balance >= $cost;
                ?>
                <article class="panel reward-card">
                    <div class="reward-card__top">
                        <span class="status-badge <?= $inStock ? 'status-badge--available' : 'status-badge--unavailable' ?>"><?= $inStock ? 'In stock' : 'Out of stock' ?></span>
                        <span class="reward-stock"><?= $inStock ? number_format($stock) . ' available' : 'Currently unavailable' ?></span>
                    </div>
                    <h2><?= e($reward['name']) ?></h2>
                    <p><?= e($reward['description'] ?: 'A reward available through the loyalty programme.') ?></p>
                    <div class="reward-card__footer">
                        <strong><?= number_format($cost) ?> points</strong>
                        <?php if (!$inStock): ?>
                            <span class="muted-copy">Not available</span>
                        <?php elseif (!$affordable): ?>
                            <span class="muted-copy">You need <?= number_format($cost - $balance) ?> more points</span>
                        <?php else: ?>
                            <a class="button button--primary" href="<?= e(url('/redeem.php?id=' . (int) $reward['id'])) ?>">Review request</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
