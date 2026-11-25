<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if ($app['pdo'] instanceof PDO) {
    App\Middleware\AuthMiddleware::redirectIfAuthenticated($app['pdo']);
}
$pageTitle = 'Welcome';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell">
    <section class="hero-panel" aria-labelledby="page-heading">
        <p class="eyebrow">Customer Loyalty Rewards</p>
        <h1 id="page-heading">Every box can bring you closer to a reward.</h1>
        <p class="hero-copy">
            Join the programme, reveal the unique code inside your product box, and turn valid product codes into loyalty points.
            Use your points to request rewards from the available catalogue.
        </p>
        <div class="hero-actions">
            <a class="button button--primary" href="<?= e(url('/register.php')) ?>">Join the programme</a>
            <a class="button button--secondary" href="<?= e(url('/login.php')) ?>">Sign in</a>
        </div>
    </section>

    <section class="steps-grid" aria-label="How the rewards programme works">
        <article class="panel step-card">
            <span class="step-number" aria-hidden="true">1</span>
            <h2>Create your account</h2>
            <p>Register with your name and email address. A phone number is optional.</p>
        </article>
        <article class="panel step-card">
            <span class="step-number" aria-hidden="true">2</span>
            <h2>Reveal and claim</h2>
            <p>Find the 16-character code inside your sealed box and enter it after signing in.</p>
        </article>
        <article class="panel step-card">
            <span class="step-number" aria-hidden="true">3</span>
            <h2>Earn and redeem</h2>
            <p>Valid claims add points to your account. Browse the reward catalogue and submit a request.</p>
        </article>
    </section>

    <section class="note-panel" aria-labelledby="purchase-proof-heading">
        <h2 id="purchase-proof-heading">How a product is verified</h2>
        <p>Each eligible box contains a unique concealed code. Possession of a valid code is the programme's proof-of-purchase credential; it does not independently verify a retailer payment or receipt.</p>
    </section>
</main>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
