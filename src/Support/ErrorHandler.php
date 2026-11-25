<?php
declare(strict_types=1);

namespace App\Support;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(string $correlationId): void
    {
        // Public responses use the controlled exception page in every environment.
        // Detailed diagnostics stay in the protected log, not in browser output.
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line
        ): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $exception) use ($correlationId): void {
            Logger::exception($exception, $correlationId);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, "Unexpected error. Reference: {$correlationId}" . PHP_EOL);
                return;
            }

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
                header('Cache-Control: no-store');
            }
            $reference = htmlspecialchars($correlationId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                . '<title>Something went wrong</title></head><body>'
                . '<main><h1>Something went wrong</h1>'
                . '<p>The request could not be completed. Please try again later.</p>'
                . '<p>Reference: <code>' . $reference . '</code></p>'
                . '</main></body></html>';
        });
    }
}
