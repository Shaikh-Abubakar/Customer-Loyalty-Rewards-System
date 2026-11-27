<?php
declare(strict_types=1);

namespace App\Support;

final class SecurityHeaders
{
    public static function send(string $nonce): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; frame-src 'none'; img-src 'self'; font-src 'self'; style-src 'self'; script-src 'self' 'nonce-{$nonce}'; connect-src 'self'; manifest-src 'self'");
        // Stage 2 explicitly permits local XAMPP over HTTP; advertise HSTS only on HTTPS.
        if (SessionManager::httpsIsActive()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
