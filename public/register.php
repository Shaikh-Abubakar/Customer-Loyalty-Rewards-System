<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\AuthService;
use App\Support\Http;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$pdo = $app['pdo'];
Http::privateNoStore();
AuthMiddleware::redirectIfAuthenticated($pdo);

$values = ['name' => '', 'email' => '', 'phone' => ''];
$errors = [];
$generalMessage = '';
if (request_method_is('POST')) {
    require_method('POST');
    require_csrf();
    foreach (array_keys($values) as $key) {
        $values[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }
    $result = (new AuthService($pdo))->register([
        'name' => $values['name'],
        'email' => $values['email'],
        'phone' => $values['phone'],
        'password' => is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
        'password_confirmation' => is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '',
    ], Http::clientIp());
    if ($result['status'] === 'success') {
        \App\Support\Flash::set('success', 'Your account is ready. Welcome to the rewards programme.');
        redirect_to('/dashboard.php');
    }
    if ($result['status'] === 'rate_limited') {
        $generalMessage = 'Too many registration attempts from this connection. Please try again later.';
    } elseif ($result['status'] === 'duplicate') {
        $generalMessage = 'If this email can be used, your account is ready. You can sign in if you already have an account.';
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Create an account';
$headerRole = 'visitor';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell page-shell--narrow">
    <section class="panel form-panel" aria-labelledby="page-heading">
        <p class="eyebrow">Join the programme</p>
        <h1 id="page-heading">Create your customer account</h1>
        <p class="muted-copy">Use your email address to sign in and keep your points and redemption requests together.</p>

        <?php if ($generalMessage !== ''): ?>
            <div class="alert alert--info" role="status"><span class="alert__label">Information</span><span><?= e($generalMessage) ?></span></div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert">
                <span class="alert__label">Please check the form</span>
                <span>Correct the highlighted fields and submit the form again.</span>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/register.php')) ?>" class="stack-form" novalidate>
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" maxlength="120" autocomplete="name" required value="<?= e($values['name']) ?>" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
                <?php if (isset($errors['name'])): ?><span class="form-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= e($values['email']) ?>" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                <?php if (isset($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="phone">Phone number <span class="optional-label">Optional</span></label>
                <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= e($values['phone']) ?>" aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>">
                <?php if (isset($errors['phone'])): ?><span class="form-error"><?= e($errors['phone']) ?></span><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" maxlength="72" aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>">
                <span class="form-hint">Use at least 12 characters. Passwords are never stored as plain text.</span>
                <?php if (isset($errors['password'])): ?><span class="form-error"><?= e($errors['password']) ?></span><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required aria-invalid="<?= isset($errors['password_confirmation']) ? 'true' : 'false' ?>">
                <?php if (isset($errors['password_confirmation'])): ?><span class="form-error"><?= e($errors['password_confirmation']) ?></span><?php endif; ?>
            </div>
            <button type="submit" class="button button--primary">Create account</button>
        </form>
        <p class="form-switch">Already registered? <a href="<?= e(url('/login.php')) ?>">Sign in</a></p>
    </section>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
