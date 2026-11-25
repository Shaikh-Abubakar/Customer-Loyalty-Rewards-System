<?php
declare(strict_types=1);

namespace App\Support;

final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));
            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $name)) {
                continue;
            }

            // Respect environment variables injected by Apache, PHP-FPM or the shell.
            if (getenv($name) !== false) {
                continue;
            }

            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                    if ($first === '"') {
                        $value = stripcslashes($value);
                    }
                } else {
                    $commentPosition = strpos($value, ' #');
                    if ($commentPosition !== false) {
                        $value = rtrim(substr($value, 0, $commentPosition));
                    }
                }
            }

            self::$values[$name] = $value;
            putenv($name . '=' . $value);
        }
    }

    public static function get(string $name, ?string $default = null): ?string
    {
        $environmentValue = getenv($name);
        if ($environmentValue !== false) {
            return (string) $environmentValue;
        }

        return self::$values[$name] ?? $default;
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        $values = self::$values;
        foreach ($_ENV as $name => $value) {
            if (is_string($name) && is_scalar($value)) {
                $values[$name] = (string) $value;
            }
        }

        // getenv() without a name also includes variables supplied by the process
        // environment, even when PHP's variables_order omits them from $_ENV.
        $processValues = getenv();
        if (is_array($processValues)) {
            foreach ($processValues as $name => $value) {
                if (is_string($name) && is_scalar($value)) {
                    $values[$name] = (string) $value;
                }
            }
        }

        return $values;
    }
}
