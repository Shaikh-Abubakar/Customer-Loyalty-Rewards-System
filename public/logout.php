<?php
declare(strict_types=1);

use App\Service\AuthService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
require_method('POST');
require_csrf();
AuthService::logoutCurrentSession();
redirect_to('/');
