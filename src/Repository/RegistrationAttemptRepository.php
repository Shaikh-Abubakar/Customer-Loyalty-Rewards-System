<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class RegistrationAttemptRepository
{
    public function __construct(private PDO $pdo) {}

    public function countSubmittedByIp(string $ip, int $windowSeconds): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM registration_attempts\n             WHERE ip_address = :ip AND outcome = 'submitted' AND created_at >= :since"
        );
        $statement->execute([
            'ip' => $ip,
            'since' => gmdate('Y-m-d H:i:s', time() - $windowSeconds),
        ]);
        return (int) $statement->fetchColumn();
    }

    public function record(string $ip, string $outcome): void
    {
        if (!in_array($outcome, ['submitted', 'rate_limited'], true)) {
            throw new \InvalidArgumentException('Invalid registration attempt outcome.');
        }
        $statement = $this->pdo->prepare(
            'INSERT INTO registration_attempts (ip_address, outcome, created_at) VALUES (:ip, :outcome, UTC_TIMESTAMP())'
        );
        $statement->execute(['ip' => $ip, 'outcome' => $outcome]);
    }
}
