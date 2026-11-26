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
    public function findForAdmin(int $redemptionId): ?array
    {
        $stmt=$this->pdo->prepare('SELECT id,user_id,reward_id,points_cost,status FROM redemptions WHERE id=:id LIMIT 1');
        $stmt->execute(['id'=>$redemptionId]);
        $row=$stmt->fetch();
        return is_array($row)?$row:null;
    }

    public function lockForAdmin(int $redemptionId): ?array
    {
        $stmt=$this->pdo->prepare('SELECT id,user_id,reward_id,points_cost,status FROM redemptions WHERE id=:id FOR UPDATE');
        $stmt->execute(['id'=>$redemptionId]);
        $row=$stmt->fetch();
        return is_array($row)?$row:null;
    }

    public function markFulfilled(int $id,int $adminId,?string $note): bool
    {
        $stmt=$this->pdo->prepare("UPDATE redemptions SET status='fulfilled',fulfilled_at=UTC_TIMESTAMP(),fulfilled_by_user_id=:admin,fulfilment_note=:note WHERE id=:id AND status='pending'");
        $stmt->execute(['admin'=>$adminId,'note'=>$note,'id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function markCancelled(int $id,int $adminId,string $reason): bool
    {
        $stmt=$this->pdo->prepare("UPDATE redemptions SET status='cancelled',cancelled_at=UTC_TIMESTAMP(),cancelled_by_user_id=:admin,cancel_reason=:reason WHERE id=:id AND status='pending'");
        $stmt->execute(['admin'=>$adminId,'reason'=>$reason,'id'=>$id]);
        return $stmt->rowCount()===1;
    }

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
