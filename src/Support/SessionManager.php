<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class SessionManager
{
    /** @param array{session_name:string,idle_timeout:int,cookie_secure:bool,cookie_samesite:string} $settings */
    public static function start(array $settings): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (headers_sent()) {
            throw new RuntimeException('Session cannot start after response output has begun.');
        }

        $isHttps = self::httpsIsActive();
        $secure = $isHttps || (bool) ($settings['cookie_secure'] ?? false);
        $sameSite = in_array($settings['cookie_samesite'] ?? 'Lax', ['Lax', 'Strict', 'None'], true)
            ? $settings['cookie_samesite']
            : 'Lax';
        if ($sameSite === 'None' && !$secure) {
            $sameSite = 'Lax';
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) max(300, (int) $settings['idle_timeout']));

        session_name((string) $settings['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        if (!session_start()) {
            throw new RuntimeException('Session initialization failed.');
        }

        $now = time();
        $idleTimeout = max(300, (int) $settings['idle_timeout']);
        $lastActivity = isset($_SESSION['_last_activity']) ? (int) $_SESSION['_last_activity'] : $now;
        if (($now - $lastActivity) > $idleTimeout) {
            $_SESSION = [];
            self::regenerateId();
        }
        $_SESSION['_last_activity'] = $now;
    }

    public static function regenerateId(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('An active session is required to regenerate its ID.');
        }
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Session ID regeneration failed.');
        }
    }

    public static function httpsIsActive(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        return ($https !== '' && $https !== 'off' && $https !== '0')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}
