<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class RedemptionRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByUserAndToken(int $userId, string $token): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, reward_id, points_cost, status, requested_at\n             FROM redemptions WHERE user_id = :user_id AND idempotency_token = :token LIMIT 1"
        );
        $statement->execute(['user_id' => $userId, 'token' => $token]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(int $userId, int $rewardId, int $pointsCost, string $token): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO redemptions\n             (user_id, reward_id, points_cost, status, idempotency_token, requested_at)\n             VALUES (:user_id, :reward_id, :points_cost, 'pending', :token, UTC_TIMESTAMP())"
        );
        $statement->execute([
            'user_id' => $userId,
            'reward_id' => $rewardId,
            'points_cost' => $pointsCost,
            'token' => $token,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function recentByUser(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $statement = $this->pdo->prepare(
            "SELECT rd.id, rd.points_cost, rd.status, rd.requested_at, r.name AS reward_name\n             FROM redemptions rd INNER JOIN rewards r ON r.id = rd.reward_id\n             WHERE rd.user_id = :user_id ORDER BY rd.id DESC LIMIT {$limit}"
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function allByUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT rd.id, rd.points_cost, rd.status, rd.requested_at, r.name AS reward_name\n             FROM redemptions rd INNER JOIN rewards r ON r.id = rd.reward_id\n             WHERE rd.user_id = :user_id ORDER BY rd.id DESC"
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }
}
