<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function createCustomer(string $email, string $passwordHash): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, role, status, created_at, updated_at)\n             VALUES (:email, :password_hash, 'customer', 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        );
        $statement->execute(['email' => $email, 'password_hash' => $passwordHash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, role, status FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function lockActiveCustomer(int $userId): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT id FROM users WHERE id = :id AND role = 'customer' AND status = 'active' FOR UPDATE"
        );
        $statement->execute(['id' => $userId]);
        return $statement->fetchColumn() !== false;
    }

    public function lockForLogin(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, role, status, password_hash FROM users WHERE id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findCredentials(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id,email,password_hash,role,status FROM users WHERE id=:id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :hash, updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute(['hash' => $passwordHash, 'id' => $userId]);
    }

    public function lockCustomerRow(int $userId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id,status FROM users WHERE id=:id AND role='customer' FOR UPDATE");
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function changeCustomerStatus(int $userId, string $status): void
    {
        $statement = $this->pdo->prepare("UPDATE users SET status=:status,updated_at=UTC_TIMESTAMP() WHERE id=:id AND role='customer'");
        $statement->execute(['status' => $status, 'id' => $userId]);
    }

    public function createAdmin(string $email, string $passwordHash): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO users (email,password_hash,role,status,created_at,updated_at)
             VALUES (:email,:hash,'admin','active',UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $statement->execute(['email' => $email, 'hash' => $passwordHash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateLastLogin(int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET last_login_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute(['id' => $userId]);
    }
}
