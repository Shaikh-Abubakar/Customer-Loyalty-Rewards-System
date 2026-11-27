<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use RuntimeException;
use Throwable;

/** Serializes rate-limit count/check/record sequences across concurrent PHP workers. */
final class RateLimitGuard
{
    /**
     * @param list<array{scope:string,subject:string}> $subjects
     */
    public static function run(PDO $pdo, array $subjects, callable $callback): mixed
    {
        $names = [];
        foreach ($subjects as $item) {
            $scope = (string) ($item['scope'] ?? '');
            $subject = (string) ($item['subject'] ?? '');
            if (!preg_match('/^[a-z][a-z0-9_-]{1,30}$/D', $scope) || $subject === '') {
                throw new RuntimeException('Rate-limit lock parameters are invalid.');
            }
            // MySQL named locks are limited to 64 characters; SHA-256 keeps the
            // original identifier (email, IP, or account) out of server metadata.
            $names[] = 'loyalty:rl:' . substr(hash('sha256', $scope . "\0" . $subject), 0, 48);
        }
        $names = array_values(array_unique($names));
        sort($names, SORT_STRING);

        $acquired = [];
        try {
            foreach ($names as $name) {
                $statement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 5)');
                $statement->execute(['lock_name' => $name]);
                if ((int) $statement->fetchColumn() !== 1) {
                    throw new RuntimeException('The rate-limit coordinator is unavailable. Try again shortly.');
                }
                $acquired[] = $name;
            }
            return $callback();
        } finally {
            foreach (array_reverse($acquired) as $name) {
                try {
                    $statement = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
                    $statement->execute(['lock_name' => $name]);
                    $statement->fetchColumn();
                } catch (Throwable) {
                    // Connection teardown also releases named locks. Never mask the
                    // original application outcome with a release diagnostic.
                }
            }
        }
    }

    public static function lockName(string $scope, string $subject): string
    {
        return 'loyalty:rl:' . substr(hash('sha256', $scope . "\0" . $subject), 0, 48);
    }
}
