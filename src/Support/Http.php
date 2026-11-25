<?php
declare(strict_types=1);

namespace App\Support;

final class Http
{
    public static function privateNoStore(): void
    {
        if (!headers_sent()) {
            header('Cache-Control: no-store, private');
            header('Pragma: no-cache');
            header('Referrer-Policy: no-referrer');
        }
    }

    public static function clientIp(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    public static function textLength(string $value): int
    {
        if (preg_match_all('/./us', $value, $matches) !== false) {
            return count($matches[0]);
        }
        return strlen($value);
    }
}
