<?php
declare(strict_types=1);

function admin_page_start(string $title,string $active): void
{
    $pageTitle=$title;
    $headerRole='admin';
    require APP_ROOT.'/templates/layout/header.php';
    $links=[
        'overview'=>['Overview','/admin/index.php'],
        'products'=>['Products','/admin/products.php'],
        'batches'=>['Batches','/admin/batches.php'],
        'customers'=>['Customers','/admin/customers.php'],
        'rewards'=>['Rewards','/admin/rewards.php'],
        'redemptions'=>['Redemptions','/admin/redemptions.php'],
        'fraud'=>['Fraud','/admin/fraud.php'],
        'audit'=>['Audit log','/admin/audit.php'],
        'reports'=>['Reports','/admin/reports.php'],
        'reconciliation'=>['Reconciliation','/admin/reconciliation.php'],
    ];
    echo '<div class="container admin-shell"><aside class="admin-sidebar"><p class="eyebrow">Factory owner</p><h2 class="admin-sidebar__title">Administration</h2><nav class="admin-nav" aria-label="Administrator navigation">';
    foreach ($links as $key=>$link) {
        $current=$key===$active?' aria-current="page"':'';
        echo '<a'.$current.' href="'.e(url($link[1])).'">'.e($link[0]).'</a>';
    }
    echo '<form action="'.e(url('/logout.php')).'" method="post" class="admin-signout">'.csrf_field().'<button class="button button--secondary button--small" type="submit">Sign out</button></form>';
    echo '</nav></aside><main id="main-content" class="admin-main">';
    echo '<p class="eyebrow">Administrator workspace</p><h1>'.e($title).'</h1>';
}

function admin_page_end(): void
{
    echo '</main></div>';
    require APP_ROOT.'/templates/layout/footer.php';
}

function admin_context(array $app): array
{
    return [
        (int)$app['admin_user']['id'],
        \App\Support\Http::clientIp(),
        (string)($app['correlation_id']??''),
    ];
}
