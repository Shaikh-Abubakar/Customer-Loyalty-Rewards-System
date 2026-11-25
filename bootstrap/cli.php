<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';
require_once APP_ROOT . '/src/Support/helpers.php';

use App\Support\Env;
use App\Support\ErrorHandler;
use App\Support\Logger;
use App\Support\RequestContext;

Env::load(APP_ROOT . DIRECTORY_SEPARATOR . '.env');
$correlationId = RequestContext::correlationId();
Logger::configure(APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs');
ErrorHandler::register($correlationId);
$config = require APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
date_default_timezone_set($config['app']['timezone']);

$GLOBALS['app_config'] = $config;
$GLOBALS['correlation_id'] = $correlationId;

return [
    'config' => $config,
    'correlation_id' => $correlationId,
];
