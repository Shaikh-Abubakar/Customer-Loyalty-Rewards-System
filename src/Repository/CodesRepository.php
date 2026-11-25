<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CodesRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByHmac(string $hash, int $keyId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, batch_id, key_id, code_hash, status, claimed_by_user_id\n             FROM codes WHERE code_hash = :hash AND key_id = :key_id LIMIT 1"
        );
        $statement->bindValue(':hash', $hash, PDO::PARAM_LOB);
        $statement->bindValue(':key_id', $keyId, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();
        if (!is_array($row) || !is_string($row['code_hash'] ?? null) || !hash_equals($hash, $row['code_hash'])) {
            return null;
        }
        unset($row['code_hash']);
        return $row;
    }

    public function lockBatch(int $batchId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, status, points_per_code FROM batches WHERE id = :id LOCK IN SHARE MODE'
        );
        $statement->execute(['id' => $batchId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function markClaimed(int $codeId, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE codes SET status = 'claimed', claimed_by_user_id = :user_id, claimed_at = UTC_TIMESTAMP()\n             WHERE id = :id AND status = 'unclaimed'"
        );
        $statement->execute(['user_id' => $userId, 'id' => $codeId]);
        return $statement->rowCount() === 1;
    }

    public function findClaimState(int $codeId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT status, claimed_by_user_id FROM codes WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $codeId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findBatchStatus(int $batchId): ?string
    {
        $statement = $this->pdo->prepare(
            'SELECT status FROM batches WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $batchId]);
        $status = $statement->fetchColumn();
        return is_string($status) ? $status : null;
    }
}
