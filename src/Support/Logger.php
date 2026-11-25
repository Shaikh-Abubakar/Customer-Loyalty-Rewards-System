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
            self::clean($exception->getMessage()),
            self::clean($exception->getFile()),
            $exception->getLine(),
            PHP_EOL
        );

        $directory = self::$logDirectory;
        $file = $directory !== '' ? $directory . DIRECTORY_SEPARATOR . 'application.log' : '';
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
            self::clean($message),
            PHP_EOL
        );
        $directory = self::$logDirectory;
        $file = $directory !== '' ? $directory . DIRECTORY_SEPARATOR . 'application.log' : '';
        if ($file !== '' && is_dir($directory) && is_writable($directory)) {
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
            return;
        }
        error_log(trim($line));
    }

    private static function clean(string $value): string
    {
        return str_replace(["\r", "\n", "\0"], ' ', $value);
    }
}
