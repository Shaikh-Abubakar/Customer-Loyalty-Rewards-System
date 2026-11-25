<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ClaimAttemptRepository
{
    public function __construct(private PDO $pdo) {}

    public function countFailuresByUser(int $userId, int $windowSeconds): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM claim_attempts\n             WHERE user_id = :user_id AND outcome IN ('invalid_format','not_found','already_claimed','batch_inactive','code_voided')\n             AND created_at >= :since"
        );
        $statement->execute([
            'user_id' => $userId,
            'since' => gmdate('Y-m-d H:i:s', time() - $windowSeconds),
        ]);
        return (int) $statement->fetchColumn();
    }

    public function countFailuresByIp(string $ip, int $windowSeconds): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM claim_attempts\n             WHERE ip_address = :ip AND outcome IN ('invalid_format','not_found','already_claimed','batch_inactive','code_voided')\n             AND created_at >= :since"
        );
        $statement->execute([
            'ip' => $ip,
            'since' => gmdate('Y-m-d H:i:s', time() - $windowSeconds),
        ]);
        return (int) $statement->fetchColumn();
    }

    public function record(int $userId, string $ip, string $outcome, ?int $codeId = null): void
    {
        if (!in_array($outcome, [
            'success', 'invalid_format', 'not_found', 'already_claimed', 'batch_inactive',
            'code_voided', 'rate_limited'
        ], true)) {
            throw new \InvalidArgumentException('Invalid claim-attempt outcome.');
        }
        $statement = $this->pdo->prepare(
            "INSERT INTO claim_attempts (user_id, ip_address, outcome, code_id, created_at)\n             VALUES (:user_id, :ip, :outcome, :code_id, UTC_TIMESTAMP())"
        );
        $statement->execute([
            'user_id' => $userId,
            'ip' => $ip,
            'outcome' => $outcome,
            'code_id' => $codeId,
        ]);
    }

    public function countSuccessfulByUser(int $userId, int $windowSeconds): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM claim_attempts\n             WHERE user_id = :user_id AND outcome = 'success' AND created_at >= :since"
        );
        $statement->execute([
            'user_id' => $userId,
            'since' => gmdate('Y-m-d H:i:s', time() - $windowSeconds),
        ]);
        return (int) $statement->fetchColumn();
    }

    public function countDistinctSuccessfulUsersByIp(string $ip, int $windowSeconds): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM claim_attempts\n             WHERE ip_address = :ip AND outcome = 'success' AND created_at >= :since"
        );
        $statement->execute([
            'ip' => $ip,
            'since' => gmdate('Y-m-d H:i:s', time() - $windowSeconds),
        ]);
        return (int) $statement->fetchColumn();
    }
}
