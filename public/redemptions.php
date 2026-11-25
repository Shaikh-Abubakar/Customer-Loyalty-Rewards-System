<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\RedemptionService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$currentUser = AuthMiddleware::requireCustomer($app['pdo']);
$headerRole = 'customer';
$redemptions = (new RedemptionService($app['pdo']))->all((int) $currentUser['id']);
$highlightId = isset($_GET['ref']) && ctype_digit((string) $_GET['ref']) ? (int) $_GET['ref'] : 0;

$pageTitle = 'My redemptions';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell">
    <div class="page-heading-row">
        <div><p class="eyebrow">Track your requests</p><h1>My redemptions</h1><p class="muted-copy">Each request is for one reward unit. Status updates are managed by the factory.</p></div>
        <a class="button button--primary" href="<?= e(url('/rewards.php')) ?>">Browse rewards</a>
    </div>
    <section class="panel" aria-labelledby="redemptions-heading">
        <h2 id="redemptions-heading" class="visually-hidden">Redemption requests</h2>
        <?php if ($redemptions === []): ?>
            <div class="empty-state"><h2>No redemption requests yet</h2><p>Browse the rewards catalogue when you are ready to exchange points.</p><a class="button button--primary" href="<?= e(url('/rewards.php')) ?>">View rewards</a></div>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <caption class="table-caption">Your reward requests, newest first</caption>
                    <thead><tr><th scope="col">Request</th><th scope="col">Reward</th><th scope="col">Points</th><th scope="col">Requested</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($redemptions as $redemption): ?>
                        <tr<?= $highlightId === (int) $redemption['id'] ? ' class="highlight-row"' : '' ?>>
                            <td>#<?= (int) $redemption['id'] ?></td>
                            <td><?= e($redemption['reward_name']) ?></td>
                            <td><?= number_format((int) $redemption['points_cost']) ?></td>
                            <td><?= e(date('M j, Y · g:i a', strtotime((string) $redemption['requested_at']))) ?></td>
                            <td>
                                <span class="status-badge status-badge--<?= e($redemption['status']) ?>"><?= e(ucfirst($redemption['status'])) ?></span>
                                <?php if ($redemption['status'] === 'pending'): ?><span class="status-explanation">Awaiting factory processing</span>
                                <?php elseif ($redemption['status'] === 'fulfilled'): ?><span class="status-explanation">Ready/collected as arranged</span>
                                <?php else: ?><span class="status-explanation">Cancelled by the factory; points returned through a ledger entry</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
