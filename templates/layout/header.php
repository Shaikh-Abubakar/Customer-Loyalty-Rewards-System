<?php
declare(strict_types=1);

use App\Support\Flash;

$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Welcome';
$appName = (string) app_config('app.name', 'Customer Loyalty Rewards');
$hasSessionUser = (int) ($_SESSION['auth_user_id'] ?? 0) > 0;
$headerRole = isset($headerRole) && is_string($headerRole) ? $headerRole : 'customer';
$flashNotice = Flash::consume();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1D4ED8">
    <meta name="description" content="A simple customer loyalty rewards programme.">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/customer.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($appName) ?> home">
            <span class="brand-mark" aria-hidden="true">LR</span>
            <span><?= e($appName) ?></span>
        </a>
        <nav class="main-nav" aria-label="Main navigation">
            <?php if ($hasSessionUser && $headerRole === 'admin'): ?>
                <a href="<?= e(url('/admin/index.php')) ?>">Admin home</a>
            <?php elseif ($hasSessionUser): ?>
                <a href="<?= e(url('/dashboard.php')) ?>">Dashboard</a>
                <a href="<?= e(url('/claim.php')) ?>">Claim code</a>
                <a href="<?= e(url('/history.php')) ?>">Points history</a>
                <a href="<?= e(url('/rewards.php')) ?>">Rewards</a>
                <a href="<?= e(url('/redemptions.php')) ?>">My redemptions</a>
                <form action="<?= e(url('/logout.php')) ?>" method="post" class="nav-logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="nav-logout__button">Sign out</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('/register.php')) ?>">Join</a>
                <a href="<?= e(url('/login.php')) ?>">Sign in</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php if ($flashNotice !== null): ?>
    <div class="container flash-wrap">
        <?php
        $alertType = $flashNotice['type'];
        $alertMessage = $flashNotice['message'];
        require APP_ROOT . '/templates/partials/status_alert.php';
        ?>
    </div>
<?php endif; ?>
