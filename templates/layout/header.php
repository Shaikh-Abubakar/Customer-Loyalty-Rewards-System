<?php
declare(strict_types=1);

$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Welcome';
$appName = (string) app_config('app.name', 'Customer Loyalty Rewards');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1D4ED8">
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
        <span class="header-label">Project foundation</span>
    </div>
</header>
