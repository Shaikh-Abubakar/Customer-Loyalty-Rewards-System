<?php
declare(strict_types=1);

namespace App\Support;

final class CodeFormat
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTVWXYZ';
    public const LENGTH = 16;

    public static function normalize(string $input): string
    {
        $normalised = strtoupper($input);
        return str_replace([' ', '-'], '', $normalised);
    }

    public static function isValid(string $normalised): bool
    {
        return strlen($normalised) === self::LENGTH
            && preg_match('/^[23456789ABCDEFGHJKMNPQRSTVWXYZ]{16}$/D', $normalised) === 1;
    }
}
