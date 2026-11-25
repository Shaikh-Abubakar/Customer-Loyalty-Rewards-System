<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\LedgerService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$currentUser = AuthMiddleware::requireCustomer($app['pdo']);
$headerRole = 'customer';
$userId = (int) $currentUser['id'];
$ledger = new LedgerService($app['pdo']);
$summary = $ledger->summary($userId);
$beforeId = isset($_GET['before']) && ctype_digit((string) $_GET['before']) && (int) $_GET['before'] > 0
    ? (int) $_GET['before']
    : null;
$entries = $ledger->history($userId, $beforeId, 25);
$lastEntryId = $entries !== [] ? (int) $entries[count($entries) - 1]['id'] : null;

$pageTitle = 'Points history';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell">
    <div class="page-heading-row">
        <div><p class="eyebrow">Your account</p><h1>Points history</h1><p class="muted-copy">Every points change is recorded here. Past entries cannot be edited.</p></div>
        <div class="balance-chip"><span>Current balance</span><strong><?= number_format($summary['balance']) ?> points</strong></div>
    </div>
    <section class="panel" aria-labelledby="history-heading">
        <h2 id="history-heading" class="visually-hidden">Points transactions</h2>
        <?php if ($entries === []): ?>
            <div class="empty-state"><h2>No points entries yet</h2><p>Claim a valid product code to see your first points transaction.</p><a class="button button--primary" href="<?= e(url('/claim.php')) ?>">Claim a code</a></div>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <caption class="table-caption">Your latest points transactions, newest first</caption>
                    <thead><tr><th scope="col">Activity</th><th scope="col">Date</th><th scope="col">Points change</th></tr></thead>
                    <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= e($entry['description']) ?></td>
                            <td><?= e(date('M j, Y · g:i a', strtotime((string) $entry['created_at']))) ?></td>
                            <td class="numeric-cell <?= (int) $entry['points_delta'] >= 0 ? 'points-change--positive' : 'points-change--negative' ?>">
                                <?= (int) $entry['points_delta'] > 0 ? '+' : '' ?><?= number_format((int) $entry['points_delta']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($entries) === 25 && $lastEntryId !== null): ?>
                <p class="pagination-link"><a class="button button--secondary" href="<?= e(url('/history.php?before=' . $lastEntryId)) ?>">View older entries</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
