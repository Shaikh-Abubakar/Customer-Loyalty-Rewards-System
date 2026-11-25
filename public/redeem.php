<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\LedgerService;
use App\Service\RedemptionService;
use App\Service\RewardService;
use App\Support\Flash;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$pdo = $app['pdo'];
$currentUser = AuthMiddleware::requireCustomer($pdo);
$headerRole = 'customer';
$userId = (int) $currentUser['id'];
$rewardService = new RewardService($pdo);

if (request_method_is('POST')) {
    require_method('POST');
    require_csrf();
    $rewardId = isset($_POST['reward_id']) && ctype_digit((string) $_POST['reward_id']) ? (int) $_POST['reward_id'] : 0;
    $token = is_string($_POST['idempotency_token'] ?? null) ? $_POST['idempotency_token'] : '';
    $result = (new RedemptionService($pdo))->request($userId, $rewardId, $token);
    if ($result['status'] === 'success') {
        Flash::set(
            'success',
            'Request #' . (int) $result['redemption_id'] . ' submitted for ' . number_format((int) $result['points_cost'])
                . ' points. Your remaining balance is ' . number_format((int) $result['balance']) . ' points. Status: pending.'
        );
        redirect_to('/redemptions.php?ref=' . (int) $result['redemption_id']);
    }
    if ($result['status'] === 'duplicate') {
        Flash::set('info', 'This request was already submitted. Your existing request is shown below.');
        redirect_to('/redemptions.php?ref=' . (int) $result['redemption_id']);
    }
    if ($result['status'] === 'insufficient_points') {
        Flash::set('warning', 'You do not have enough points for this reward.');
    } elseif ($result['status'] === 'out_of_stock') {
        Flash::set('warning', 'This reward is out of stock. Your points were not deducted.');
    } elseif ($result['status'] === 'unavailable') {
        Flash::set('warning', 'This reward is no longer available. Your points were not deducted.');
    } elseif ($result['status'] === 'account_inactive') {
        Flash::set('warning', 'Your account is no longer active. Contact support for help.');
        redirect_to('/login.php');
    } else {
        Flash::set('error', 'The request could not be processed. Please review the reward and try again.');
    }
    redirect_to('/rewards.php');
}

$rewardId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$reward = $rewardId > 0 ? $rewardService->find($rewardId) : null;
if ($reward === null || (int) $reward['is_active'] !== 1) {
    Flash::set('info', 'This reward is no longer available.');
    redirect_to('/rewards.php');
}
$balance = (new LedgerService($pdo))->balance($userId);
$cost = (int) $reward['points_cost'];
$inStock = (int) $reward['stock_quantity'] > 0;
$affordable = $balance >= $cost;
$idempotencyToken = bin2hex(random_bytes(16));

$pageTitle = 'Review reward request';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell page-shell--narrow">
    <section class="panel form-panel" aria-labelledby="page-heading">
        <p class="eyebrow">Confirm one reward</p>
        <h1 id="page-heading">Review your request</h1>
        <div class="confirmation-card">
            <h2><?= e($reward['name']) ?></h2>
            <p><?= e($reward['description'] ?: 'A reward available through the loyalty programme.') ?></p>
            <dl class="confirmation-list">
                <div><dt>Points required</dt><dd><?= number_format($cost) ?></dd></div>
                <div><dt>Current balance</dt><dd><?= number_format($balance) ?></dd></div>
                <div><dt>Estimated balance after request</dt><dd><?= number_format($balance - $cost) ?></dd></div>
                <div><dt>Available stock</dt><dd><?= number_format((int) $reward['stock_quantity']) ?></dd></div>
            </dl>
        </div>
        <?php if (!$inStock): ?>
            <div class="alert alert--warning" role="status"><span class="alert__label">Unavailable</span><span>This reward is out of stock. You have not spent any points.</span></div>
            <a class="button button--secondary" href="<?= e(url('/rewards.php')) ?>">Back to rewards</a>
        <?php elseif (!$affordable): ?>
            <div class="alert alert--warning" role="status"><span class="alert__label">Not enough points</span><span>You do not have enough points for this reward. Your points have not been deducted.</span></div>
            <a class="button button--secondary" href="<?= e(url('/rewards.php')) ?>">Back to rewards</a>
        <?php else: ?>
            <div class="note-panel note-panel--compact">
                <strong>Before you confirm</strong>
                <p>This creates one pending redemption request and deducts <?= number_format($cost) ?> points immediately. The reward is collected at the designated factory collection point. You cannot cancel the request yourself.</p>
            </div>
            <form method="post" action="<?= e(url('/redeem.php')) ?>" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="reward_id" value="<?= (int) $reward['id'] ?>">
                <input type="hidden" name="idempotency_token" value="<?= e($idempotencyToken) ?>">
                <button type="submit" class="button button--primary">Confirm reward request</button>
                <a class="button button--secondary" href="<?= e(url('/rewards.php')) ?>">Cancel and go back</a>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
