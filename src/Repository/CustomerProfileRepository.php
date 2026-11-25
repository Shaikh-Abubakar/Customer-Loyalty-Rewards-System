<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CustomerProfileRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(int $userId, string $fullName, ?string $phone): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO customer_profiles (user_id, full_name, phone, created_at, updated_at)\n             VALUES (:user_id, :full_name, :phone, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        );
        $statement->execute([
            'user_id' => $userId,
            'full_name' => $fullName,
            'phone' => $phone,
        ]);
    }

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT full_name, phone, contact_details FROM customer_profiles WHERE user_id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }
}
