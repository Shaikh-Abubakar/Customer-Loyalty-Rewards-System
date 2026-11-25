<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$currentUser = AuthMiddleware::requireAdmin($app['pdo']);
$headerRole = 'admin';
$pageTitle = 'Administrator area';
require dirname(__DIR__, 2) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell">
    <section class="panel">
        <p class="eyebrow">Administrator account</p>
        <h1>Administrator area</h1>
        <p>The shared authentication system has signed you in. Administrator management tools are scheduled for a later implementation stage.</p>
        <p><a class="button button--secondary" href="<?= e(url('/')) ?>">Return to programme home</a></p>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/templates/layout/footer.php'; ?>
