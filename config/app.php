<?php
declare(strict_types=1);

use App\Support\Env;

$appName = trim((string) Env::get('APP_NAME', 'Customer Loyalty Rewards'));
$appEnvironment = strtolower(trim((string) Env::get('APP_ENV', 'development')));
if (!in_array($appEnvironment, ['development', 'production'], true)) {
    $appEnvironment = 'development';
}

$baseUrl = trim((string) Env::get('APP_BASE_URL', ''));
if ($baseUrl !== '') {
    $baseUrlParts = parse_url($baseUrl);
    if (
        filter_var($baseUrl, FILTER_VALIDATE_URL) === false
        || !is_array($baseUrlParts)
        || !in_array(strtolower((string) ($baseUrlParts['scheme'] ?? '')), ['http', 'https'], true)
        || empty($baseUrlParts['host'])
        || isset($baseUrlParts['user'])
        || isset($baseUrlParts['pass'])
        || isset($baseUrlParts['query'])
        || isset($baseUrlParts['fragment'])
    ) {
        throw new RuntimeException('APP_BASE_URL must be an HTTP or HTTPS application URL without credentials or query parameters.');
    }
}

$timezone = trim((string) Env::get('APP_TIMEZONE', 'UTC'));
try {
    new DateTimeZone($timezone);
} catch (Throwable) {
    throw new RuntimeException('APP_TIMEZONE is not a supported timezone.');
}

$hmacKeys = [];
foreach (Env::all() as $name => $value) {
    if (preg_match('/^HMAC_KEY_(\d+)$/', (string) $name, $matches) === 1) {
        $hmacKeys[(int) $matches[1]] = (string) $value;
    }
}

$secureCookieValue = strtolower(trim((string) Env::get('SESSION_COOKIE_SECURE', '0')));

return [
    'app' => [
        'name' => $appName !== '' ? $appName : 'Customer Loyalty Rewards',
        'environment' => $appEnvironment,
        'base_url' => rtrim($baseUrl, '/'),
        'timezone' => $timezone,
    ],
    'database' => [
        'host' => (string) Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int) Env::get('DB_PORT', '3306'),
        'name' => (string) Env::get('DB_NAME', ''),
        'user' => (string) Env::get('DB_USER', ''),
        'password' => (string) Env::get('DB_PASSWORD', ''),
    ],
    'security' => [
        'session_name' => 'loyalty_session',
        'idle_timeout' => max(300, (int) Env::get('SESSION_IDLE_TIMEOUT', '1800')),
        'cookie_secure' => in_array($secureCookieValue, ['1', 'true', 'yes', 'on'], true),
        'cookie_samesite' => ucfirst(strtolower((string) Env::get('SESSION_COOKIE_SAMESITE', 'Lax'))),
    ],
    'hmac' => [
        'active_key_id' => (int) Env::get('HMAC_ACTIVE_KEY_ID', '0'),
        'keys' => $hmacKeys,
    ],
    'rate_limits' => [
        'login_failures' => 5,
        'login_window_seconds' => 900,
        'claim_failures' => 10,
        'claim_window_seconds' => 900,
        'successful_claims_per_user' => 20,
        'successful_claim_window_seconds' => 86400,
        'distinct_accounts_per_ip' => 10,
        'distinct_accounts_window_seconds' => 3600,
    ],
    'pagination' => [
        'default_page_size' => 25,
        'maximum_page_size' => 100,
    ],
];
