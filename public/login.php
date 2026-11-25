<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\AuthService;
use App\Support\Flash;
use App\Support\Http;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$pdo = $app['pdo'];
AuthMiddleware::redirectIfAuthenticated($pdo);
Http::privateNoStore();

$email = '';
$message = '';
$messageType = 'error';
$returnToClaim = (($_GET['return'] ?? '') === 'claim');
if (request_method_is('POST')) {
    require_method('POST');
    require_csrf();
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $returnToClaim = (($_POST['return'] ?? '') === 'claim');
    $result = (new AuthService($pdo))->login($email, $password, Http::clientIp());
    if ($result['status'] === 'success') {
        if ($result['role'] === 'admin') {
            redirect_to('/admin/index.php');
        }
        redirect_to($returnToClaim ? '/claim.php' : '/dashboard.php');
    }
    if ($result['status'] === 'locked') {
        $message = 'Too many attempts. Please try again later.';
        $messageType = 'warning';
    } else {
        $message = 'Email or password is incorrect.';
        $messageType = 'error';
    }
}

$pageTitle = 'Sign in';
$headerRole = 'visitor';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell page-shell--narrow">
    <section class="panel form-panel" aria-labelledby="page-heading">
        <p class="eyebrow">Welcome back</p>
        <h1 id="page-heading">Sign in to your account</h1>
        <p class="muted-copy">Access your points, claim product codes and track reward requests.</p>
        <?php if ($message !== ''): ?>
            <div class="alert alert--<?= e($messageType) ?>" role="<?= $messageType === 'error' ? 'alert' : 'status' ?>">
                <span class="alert__label"><?= $messageType === 'error' ? 'Sign-in failed' : 'Please wait' ?></span>
                <span><?= e($message) ?></span>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/login.php')) ?><?= $returnToClaim ? '?return=claim' : '' ?>" class="stack-form">
            <?= csrf_field() ?>
            <?php if ($returnToClaim): ?><input type="hidden" name="return" value="claim"><?php endif; ?>
            <div class="form-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="254" autocomplete="username" required value="<?= e($email) ?>">
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="button button--primary">Sign in</button>
        </form>
        <p class="form-switch">New to the programme? <a href="<?= e(url('/register.php')) ?>">Create an account</a></p>
        <p class="support-note">For account-access help, contact the factory owner. Password reset by email is not available in this first version.</p>
    </section>
</main>
<?php if ($app['csp_nonce'] !== ''): ?>
<script nonce="<?= e($app['csp_nonce']) ?>">
(function () {
    if (window.location.hash) {
        window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
    }
}());
</script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
