<?php
declare(strict_types=1);

use App\Support\Env;
use App\Support\SessionManager;

$bootstrap = require dirname(__DIR__) . '/bootstrap/cli.php';
$checks = [];
$failures = [];

$assert = static function (string $name, bool $passed) use (&$checks, &$failures): void {
    $checks[] = [$name, $passed];
    if (!$passed) {
        $failures[] = $name;
    }
};

$assert('PHP 8 runtime', PHP_VERSION_ID >= 80000);
$envTestFile = tempnam(sys_get_temp_dir(), 'loyalty_env_');
if ($envTestFile !== false) {
    file_put_contents($envTestFile, 'STAGE4_TEST_ENV="configured value"' . "\n");
    Env::load($envTestFile);
    $assert('Environment file parsing', Env::get('STAGE4_TEST_ENV') === 'configured value');
    unlink($envTestFile);
} else {
    $assert('Environment file parsing', false);
}
$assert('PDO extension available', extension_loaded('PDO'));
$assert('Session extension available', extension_loaded('session'));
$assert('OpenSSL extension available', extension_loaded('openssl'));
$assert('Application configuration loaded', is_array($bootstrap['config']) && $bootstrap['config']['app']['name'] !== '');
putenv('HMAC_KEY_77=stage4-test-key-placeholder');
$allEnvironment = Env::all();
$assert('Process environment key discovery', ($allEnvironment['HMAC_KEY_77'] ?? null) === 'stage4-test-key-placeholder');
putenv('HMAC_KEY_77');

$GLOBALS['app_config'] = $bootstrap['config'];
$escaped = e('<script>alert("x")</script>');
$assert('HTML output escaping', $escaped === '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;');
$expectedUrl = rtrim((string) $bootstrap['config']['app']['base_url'], '/') . '/assets/css/base.css';
$assert('Application URL generation', url('/assets/css/base.css') === $expectedUrl);
try {
    url('https://example.invalid/');
    $assert('External URL rejected by URL helper', false);
} catch (InvalidArgumentException) {
    $assert('External URL rejected by URL helper', true);
}

$_SERVER['HTTPS'] = 'on'; // Simulate HTTPS for the cookie-security check.
SessionManager::start($bootstrap['config']['security']);
$assert('Session initialization', session_status() === PHP_SESSION_ACTIVE);
$assert('Session strict mode', ini_get('session.use_strict_mode') === '1');
$cookie = session_get_cookie_params();
$assert('HttpOnly session cookie', (bool) $cookie['httponly'] === true);
$assert('SameSite session cookie configured', in_array($cookie['samesite'], ['Lax', 'Strict', 'None'], true));
$assert('Secure cookie enabled when HTTPS is active', (bool) $cookie['secure'] === true);
unset($_SERVER['HTTPS']);
$token = csrf_token();
$assert('CSRF token generation and verification', csrf_is_valid($token));
$assert('Invalid CSRF token rejected', !csrf_is_valid('invalid-token'));
$oldSessionId = session_id();
regenerate_session_id();
$assert('Session ID regeneration support', session_id() !== '' && session_id() !== $oldSessionId);
$assert('Request method helper', request_method_is('GET'));
session_write_close();

// Missing configuration must fail before attempting a connection and must not reveal a password.
try {
    App\Support\Database::connect([
        'host' => '127.0.0.1', 'port' => 3306, 'name' => '', 'user' => '', 'password' => 'DO_NOT_PRINT'
    ]);
    $assert('Missing database configuration is rejected safely', false);
} catch (Throwable $exception) {
    $assert(
        'Missing database configuration is rejected safely',
        str_contains($exception->getMessage(), 'configuration is incomplete')
            && !str_contains($exception->getMessage(), 'DO_NOT_PRINT')
    );
}

foreach ($checks as [$name, $passed]) {
    fwrite(STDOUT, sprintf("%s %s%s", $passed ? 'PASS' : 'FAIL', $name, PHP_EOL));
}
fwrite(STDOUT, sprintf("%d checks, %d failures.%s", count($checks), count($failures), PHP_EOL));
exit($failures === [] ? 0 : 1);
