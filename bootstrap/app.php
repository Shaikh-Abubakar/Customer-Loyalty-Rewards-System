<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';
require_once APP_ROOT . '/src/Support/helpers.php';

use App\Support\Database;
use App\Support\Env;
use App\Support\ErrorHandler;
use App\Support\Logger;
use App\Support\RequestContext;
use App\Support\SecurityHeaders;
use App\Support\SessionManager;

Env::load(APP_ROOT . DIRECTORY_SEPARATOR . '.env');
$correlationId = RequestContext::correlationId();
Logger::configure(APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs');
ErrorHandler::register($correlationId);
$config = require APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
date_default_timezone_set($config['app']['timezone']);
SessionManager::start($config['security']);
$cspNonce = base64_encode(random_bytes(18));
$GLOBALS['csp_nonce'] = $cspNonce;
SecurityHeaders::send($cspNonce);

$pdo = null;
$databaseConnected = false;
try {
    $pdo = Database::connect($config['database']);
    $databaseConnected = true;
} catch (Throwable $exception) {
    // Missing local setup is an expected first-run state; do not write it on every page view.
    $expectedSetupErrors = [
        'Database configuration is incomplete.',
        'The PHP PDO MySQL driver (pdo_mysql) is not enabled.',
    ];
    if (!in_array($exception->getMessage(), $expectedSetupErrors, true)) {
        Logger::exception($exception, $correlationId);
    }
}

$GLOBALS['app_config'] = $config;
$GLOBALS['correlation_id'] = $correlationId;
$GLOBALS['database'] = $pdo;

return [
    'config' => $config,
    'pdo' => $pdo,
    'database_connected' => $databaseConnected,
    'correlation_id' => $correlationId,
    'csp_nonce' => $cspNonce,
];
