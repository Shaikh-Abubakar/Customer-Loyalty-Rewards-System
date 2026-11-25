<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$pageTitle = 'Welcome';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell">
    <section class="hero-panel" aria-labelledby="page-heading">
        <p class="eyebrow">Customer Loyalty Rewards System</p>
        <h1 id="page-heading">A simple foundation for a better rewards experience.</h1>
        <p class="hero-copy">
            The application foundation is in place. Customer journeys, product-code claims,
            points and reward management will be added in their approved later stages.
        </p>
        <div class="hero-actions">
            <span class="button button--primary button--disabled" aria-disabled="true">Customer features coming in a later stage</span>
        </div>
    </section>

    <section class="status-grid" aria-label="Foundation status">
        <article class="status-card">
            <div class="status-card__top">
                <h2>Application</h2>
                <span class="status-pill status-pill--ready">Loaded</span>
            </div>
            <p>Configuration, secure session setup and shared helpers are loaded.</p>
        </article>
        <article class="status-card">
            <div class="status-card__top">
                <h2>Database</h2>
                <?php if ($app['database_connected']): ?>
                    <span class="status-pill status-pill--ready">Connected</span>
                <?php else: ?>
                    <span class="status-pill status-pill--pending">Not connected</span>
                <?php endif; ?>
            </div>
            <?php if ($app['database_connected']): ?>
                <p>The PDO connection is available and configured for UTF-8 and UTC.</p>
            <?php else: ?>
                <p>Set up your local <code>.env</code> file, create the database and import <code>database/schema.sql</code>. Then run the database test.</p>
            <?php endif; ?>
        </article>
    </section>

    <section class="next-panel" aria-labelledby="next-heading">
        <h2 id="next-heading">Next setup steps</h2>
        <ol>
            <li>Copy <code>.env.example</code> to <code>.env</code> and enter local database settings.</li>
            <li>Create the database and import the approved 17-table schema.</li>
            <li>Run <code>php bin/test_database.php</code> to verify the PDO connection.</li>
        </ol>
    </section>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
