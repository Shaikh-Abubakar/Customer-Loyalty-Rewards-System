<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ProductRepository
{
    public function __construct(private PDO $pdo) {}

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(string $sku, string $name, ?string $description, int $points, int $adminId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (sku,name,description,default_points,is_active,created_by_user_id,created_at,updated_at)
             VALUES (:sku,:name,:description,:points,1,:admin,UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $stmt->execute(['sku'=>$sku,'name'=>$name,'description'=>$description,'points'=>$points,'admin'=>$adminId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $sku, string $name, ?string $description, int $points): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET sku=:sku,name=:name,description=:description,default_points=:points,updated_at=UTC_TIMESTAMP() WHERE id=:id'
        );
        $stmt->execute(['sku'=>$sku,'name'=>$name,'description'=>$description,'points'=>$points,'id'=>$id]);
        return $stmt->rowCount() === 1;
    }

    public function setActive(int $id, bool $active): bool
    {
        $stmt = $this->pdo->prepare('UPDATE products SET is_active=:active,updated_at=UTC_TIMESTAMP() WHERE id=:id AND is_active<>:active_check');
        $stmt->execute(['active'=>$active?1:0,'active_check'=>$active?1:0,'id'=>$id]);
        return $stmt->rowCount() === 1;
    }
}
