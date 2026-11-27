<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class Logger
{
    private static string $logDirectory = '';

    public static function configure(string $logDirectory): void
    {
        self::$logDirectory = $logDirectory;
    }

    public static function exception(Throwable $exception, string $correlationId): void
    {
        $message = sprintf(
            "[%s] ERROR correlation_id=%s exception=%s message=%s location=%s:%d%s",
            gmdate('Y-m-d\TH:i:s\Z'),
            self::clean($correlationId),
            self::clean(get_class($exception)),
            self::redactSensitive($exception->getMessage()),
            self::clean($exception->getFile()),
            $exception->getLine(),
            PHP_EOL
        );

        $directory = self::$logDirectory;
        $file = $directory !== '' ? $directory . DIRECTORY_SEPARATOR . 'application-' . gmdate('Y-m-d') . '.log' : '';
        if ($file !== '' && is_dir($directory) && is_writable($directory)) {
            @file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
            return;
        }

        error_log(trim($message));
    }

    public static function notice(string $message, string $correlationId): void
    {
        $line = sprintf(
            "[%s] NOTICE correlation_id=%s message=%s%s",
            gmdate('Y-m-d\TH:i:s\Z'),
            self::clean($correlationId),
            self::redactSensitive($message),
            PHP_EOL
        );
        $directory = self::$logDirectory;
        $file = $directory !== '' ? $directory . DIRECTORY_SEPARATOR . 'application-' . gmdate('Y-m-d') . '.log' : '';
        if ($file !== '' && is_dir($directory) && is_writable($directory)) {
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
            return;
        }
        error_log(trim($line));
    }


    public static function redactSensitive(string $value): string
    {
        $value = self::clean($value);
        // Remove obvious key/value secrets before general code-shaped token masking.
        $value = preg_replace(
            '/(?i)\b(password|passwd|secret|csrf(?:_token)?|session(?:_id)?|authorization|api[_-]?key|hmac[_-]?key)\b\s*([=:]\s*)[^\s,;]+/',
            '$1$2[REDACTED]',
            $value
        ) ?? '[REDACTED]';
        // Product codes may appear as a compact 16-character value or as four groups.
        // Mask both forms so logging never exposes a code copied from the claim/export UI.
        $value = preg_replace('/(?<![A-Z0-9])[23456789ABCDEFGHJKMNPQRSTVWXYZ]{4}(?:[- ]?[23456789ABCDEFGHJKMNPQRSTVWXYZ]{4}){3}(?![A-Z0-9])/i', '[PRODUCT-CODE]', $value) ?? '[REDACTED]';
        // Do not print long encoded values which could be a key or digest payload.
        $value = preg_replace('/(?<![A-Za-z0-9+\/])[A-Za-z0-9+\/]{43,}={0,2}(?![A-Za-z0-9+\/])/', '[ENCODED-SECRET]', $value) ?? '[REDACTED]';
        return $value;
    }

    private static function clean(string $value): string
    {
        return str_replace(["\r", "\n", "\0"], ' ', $value);
    }
}
