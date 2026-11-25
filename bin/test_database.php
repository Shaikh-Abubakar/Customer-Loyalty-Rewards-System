<?php
declare(strict_types=1);

use App\Support\Database;
use App\Support\Logger;

$bootstrap = require dirname(__DIR__) . '/bootstrap/cli.php';

if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "FAIL: PHP extension pdo_mysql is not enabled. Enable it in the PHP CLI and Apache configurations." . PHP_EOL);
    exit(1);
}

try {
    $pdo = Database::connect($bootstrap['config']['database']);
    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    fwrite(STDOUT, "PASS: PDO connected to the configured database. Server version: {$version}" . PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    Logger::exception($exception, $bootstrap['correlation_id']);
    if (str_contains($exception->getMessage(), 'pdo_mysql')) {
        fwrite(STDERR, "FAIL: PHP extension pdo_mysql is not enabled. Enable it in the PHP CLI and Apache configurations." . PHP_EOL);
    } elseif (str_contains($exception->getMessage(), 'configuration is incomplete')) {
        fwrite(STDERR, "FAIL: Database settings are incomplete. Copy .env.example to .env and configure DB_HOST, DB_PORT, DB_NAME and DB_USER." . PHP_EOL);
    } else {
        fwrite(STDERR, "FAIL: Database connection could not be verified. Check the local database service and .env settings; technical details are in the protected application log." . PHP_EOL);
    }
    exit(1);
}
