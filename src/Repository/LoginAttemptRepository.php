<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class LoginAttemptRepository
{
    public function __construct(private PDO $pdo) {}

    public function countFailuresByIp(string $ip, int $sinceSeconds): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND succeeded = 0 AND created_at >= :since'
        );
        $statement->execute(['ip' => $ip, 'since' => gmdate('Y-m-d H:i:s', time() - $sinceSeconds)]);
        return (int) $statement->fetchColumn();
    }

    public function countFailuresByUser(int $userId, int $sinceSeconds): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE user_id = :user_id AND succeeded = 0 AND created_at >= :since'
        );
        $statement->execute(['user_id' => $userId, 'since' => gmdate('Y-m-d H:i:s', time() - $sinceSeconds)]);
        return (int) $statement->fetchColumn();
    }

    public function record(?int $userId, string $ip, bool $succeeded): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO login_attempts (user_id, ip_address, succeeded, created_at)\n             VALUES (:user_id, :ip, :succeeded, UTC_TIMESTAMP())"
        );
        $statement->execute([
            'user_id' => $userId,
            'ip' => $ip,
            'succeeded' => $succeeded ? 1 : 0,
        ]);
    }
}
