<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class RewardRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function findActive(): array
    {
        $statement = $this->pdo->query(
            "SELECT id, name, description, points_cost, stock_quantity, is_active\n             FROM rewards WHERE is_active = 1 ORDER BY points_cost ASC, name ASC"
        );
        return $statement->fetchAll();
    }

    public function findById(int $rewardId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, name, description, points_cost, stock_quantity, is_active\n             FROM rewards WHERE id = :id LIMIT 1"
        );
        $statement->execute(['id' => $rewardId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findByIdForUpdate(int $rewardId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, description, points_cost, stock_quantity, is_active
             FROM rewards WHERE id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $rewardId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function reserveOne(int $rewardId): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE rewards SET stock_quantity = stock_quantity - 1, updated_at = UTC_TIMESTAMP()\n             WHERE id = :id AND is_active = 1 AND stock_quantity > 0"
        );
        $statement->execute(['id' => $rewardId]);
        return $statement->rowCount() === 1;
    }
}
