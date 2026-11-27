<?php
declare(strict_types=1);

use App\Support\SessionManager;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        if ($value === null) {
            $value = '';
        } elseif (!is_scalar($value) && !$value instanceof Stringable) {
            $value = '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('app_config')) {
    function app_config(?string $key = null, mixed $default = null): mixed
    {
        $config = $GLOBALS['app_config'] ?? [];
        if ($key === null || $key === '') {
            return $config;
        }
        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        if (str_contains($path, "\r") || str_contains($path, "\n") || str_contains($path, '\\')) {
            throw new InvalidArgumentException('Invalid application path.');
        }
        if (str_starts_with($path, '//')) {
            throw new InvalidArgumentException('External URLs are not accepted.');
        }
        $parts = parse_url($path);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user'])) {
            throw new InvalidArgumentException('Only application-relative URLs are accepted.');
        }

        $base = rtrim((string) app_config('app.base_url', ''), '/');
        $relative = ltrim($path, '/');
        if ($relative === '') {
            return $base . '/';
        }
        return $base . '/' . $relative;
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $path, int $status = 303): void
    {
        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException('Redirect status must be 3xx.');
        }
        if (headers_sent()) {
            throw new RuntimeException('Cannot redirect after response output has begun.');
        }
        header('Location: ' . url($path), true, $status);
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('An active session is required for CSRF protection.');
        }
        if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_is_valid')) {
    function csrf_is_valid(?string $submittedToken): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !is_string($_SESSION['_csrf_token'] ?? null)) {
            return false;
        }
        return is_string($submittedToken) && hash_equals($_SESSION['_csrf_token'], $submittedToken);
    }
}

if (!function_exists('require_csrf')) {
    function require_csrf(): void
    {
        if (!request_method_is('POST')) {
            if (!headers_sent()) {
                http_response_code(405);
                header('Allow: POST');
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo 'Method not allowed.';
            exit;
        }
        $token = $_POST['_csrf'] ?? null;
        if (!is_string($token) || !csrf_is_valid($token)) {
            if (!headers_sent()) {
                http_response_code(403);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo 'The request could not be verified. Refresh the page and try again.';
            exit;
        }
    }
}

if (!function_exists('request_method')) {
    function request_method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }
}

if (!function_exists('request_method_is')) {
    function request_method_is(string $method): bool
    {
        return request_method() === strtoupper($method);
    }
}

if (!function_exists('require_method')) {
    function require_method(string $method): void
    {
        $method = strtoupper($method);
        if (!request_method_is($method)) {
            if (!headers_sent()) {
                http_response_code(405);
                header('Allow: ' . $method);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo 'Method not allowed.';
            exit;
        }
    }
}

if (!function_exists('regenerate_session_id')) {
    function regenerate_session_id(): void
    {
        SessionManager::regenerateId();
    }
}
