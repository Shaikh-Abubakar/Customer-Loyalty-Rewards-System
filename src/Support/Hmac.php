<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Hmac
{
    public const KEY_CHECK_TEXT = 'loyalty-key-check-v1';
    public const CODE_DOMAIN = 'loyalty-code-v1:';

    public static function decodeSecret(string $encoded): string
    {
        $decoded = base64_decode(trim($encoded), true);
        if ($decoded === false || strlen($decoded) < 32) {
            throw new RuntimeException('An HMAC key is missing, malformed or shorter than 32 bytes.');
        }
        return $decoded;
    }

    public static function keyCheck(string $secret): string
    {
        return hash_hmac('sha256', self::KEY_CHECK_TEXT, $secret, true);
    }

    public static function codeHash(string $normalisedCode, string $secret): string
    {
        return hash_hmac('sha256', self::CODE_DOMAIN . $normalisedCode, $secret, true);
    }
}
