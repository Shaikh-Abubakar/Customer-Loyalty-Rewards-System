<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;

$app = require __DIR__ . '/app.php';
if (!$app['pdo'] instanceof PDO) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'The database is not available. Check the local configuration and try again.';
    exit;
}
$adminUser = AuthMiddleware::requireAdmin($app['pdo']);
return $app + ['admin_user' => $adminUser];
