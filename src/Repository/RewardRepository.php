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

    public function create(string $name, ?string $description, int $cost, int $stock): int
    {
        $stmt=$this->pdo->prepare('INSERT INTO rewards (name,description,points_cost,stock_quantity,is_active,created_at,updated_at) VALUES (:name,:description,:cost,:stock,1,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
        $stmt->execute(['name'=>$name,'description'=>$description,'cost'=>$cost,'stock'=>$stock]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateDetails(int $id,string $name,?string $description,int $cost): void
    {
        $stmt=$this->pdo->prepare('UPDATE rewards SET name=:name,description=:description,points_cost=:cost,updated_at=UTC_TIMESTAMP() WHERE id=:id');
        $stmt->execute(['name'=>$name,'description'=>$description,'cost'=>$cost,'id'=>$id]);
    }

    public function setActive(int $id,bool $active): bool
    {
        $stmt=$this->pdo->prepare('UPDATE rewards SET is_active=:active,updated_at=UTC_TIMESTAMP() WHERE id=:id AND is_active<>:check_active');
        $stmt->execute(['active'=>$active?1:0,'check_active'=>$active?1:0,'id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function addStock(int $id,int $amount): bool
    {
        $stmt=$this->pdo->prepare('UPDATE rewards SET stock_quantity=stock_quantity+:amount,updated_at=UTC_TIMESTAMP() WHERE id=:id AND stock_quantity <= 4294967295-:amount');
        $stmt->execute(['amount'=>$amount,'id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function returnOne(int $id): void
    {
        $stmt=$this->pdo->prepare('UPDATE rewards SET stock_quantity=stock_quantity+1,updated_at=UTC_TIMESTAMP() WHERE id=:id AND stock_quantity < 4294967295');
        $stmt->execute(['id'=>$id]);
        if ($stmt->rowCount()!==1) { throw new \RuntimeException('Reward stock could not be restored.'); }
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
