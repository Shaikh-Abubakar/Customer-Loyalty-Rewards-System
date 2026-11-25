<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class PointsLedgerRepository
{
    public function __construct(private PDO $pdo) {}

    public function insertClaim(int $userId, int $codeId, int $points): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO points_ledger\n             (user_id, entry_type, points_delta, claim_code_id, created_by_user_id, created_at)\n             VALUES (:user_id, 'claim', :points, :code_id, :created_by, UTC_TIMESTAMP())"
        );
        $statement->execute([
            'user_id' => $userId,
            'points' => $points,
            'code_id' => $codeId,
            'created_by' => $userId,
        ]);
    }

    public function insertRedemption(int $userId, int $redemptionId, int $pointsCost): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO points_ledger\n             (user_id, entry_type, points_delta, redemption_id, created_by_user_id, created_at)\n             VALUES (:user_id, 'redemption', :points, :redemption_id, :created_by, UTC_TIMESTAMP())"
        );
        $statement->execute([
            'user_id' => $userId,
            'points' => -$pointsCost,
            'redemption_id' => $redemptionId,
            'created_by' => $userId,
        ]);
    }

    public function balance(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(points_delta), 0) FROM points_ledger WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function summary(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT\n                COALESCE(SUM(points_delta), 0) AS balance,\n                COALESCE(SUM(CASE WHEN entry_type = 'claim' THEN points_delta ELSE 0 END), 0) AS earned,\n                COALESCE(-SUM(CASE WHEN entry_type = 'redemption' THEN points_delta ELSE 0 END), 0) AS spent,\n                SUM(CASE WHEN entry_type = 'claim' THEN 1 ELSE 0 END) AS claimed_codes\n             FROM points_ledger WHERE user_id = :user_id"
        );
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch() ?: [];
        return [
            'balance' => (int) ($row['balance'] ?? 0),
            'earned' => (int) ($row['earned'] ?? 0),
            'spent' => (int) ($row['spent'] ?? 0),
            'claimed_codes' => (int) ($row['claimed_codes'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function recent(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $statement = $this->pdo->prepare(
            "SELECT pl.id, pl.entry_type, pl.points_delta, pl.reason, pl.created_at,\n                    CASE\n                      WHEN pl.entry_type = 'claim' THEN CONCAT('Code claim · Batch ', COALESCE(b.batch_code, ''))\n                      WHEN pl.entry_type = 'redemption' THEN CONCAT('Reward redemption · ', COALESCE(r.name, 'Reward'))\n                      WHEN pl.entry_type = 'reversal' THEN CONCAT('Reversal · ', COALESCE(pl.reason, 'Points returned'))\n                      ELSE CONCAT('Points adjustment · ', COALESCE(pl.reason, 'Account adjustment'))\n                    END AS description\n             FROM points_ledger pl\n             LEFT JOIN codes c ON c.id = pl.claim_code_id\n             LEFT JOIN batches b ON b.id = c.batch_id\n             LEFT JOIN redemptions rd ON rd.id = pl.redemption_id\n             LEFT JOIN rewards r ON r.id = rd.reward_id\n             WHERE pl.user_id = :user_id ORDER BY pl.id DESC LIMIT {$limit}"
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function history(int $userId, ?int $beforeId, int $limit = 25): array
    {
        $limit = max(1, min(50, $limit));
        $condition = $beforeId !== null ? ' AND pl.id < :before_id' : '';
        $statement = $this->pdo->prepare(
            "SELECT pl.id, pl.entry_type, pl.points_delta, pl.reason, pl.created_at,\n                    CASE\n                      WHEN pl.entry_type = 'claim' THEN CONCAT('Claimed a code · Batch ', COALESCE(b.batch_code, ''))\n                      WHEN pl.entry_type = 'redemption' THEN CONCAT('Redeemed reward · ', COALESCE(r.name, 'Reward'))\n                      WHEN pl.entry_type = 'reversal' THEN CONCAT('Points reversal · ', COALESCE(pl.reason, ''))\n                      ELSE CONCAT('Points adjustment · ', COALESCE(pl.reason, ''))\n                    END AS description\n             FROM points_ledger pl\n             LEFT JOIN codes c ON c.id = pl.claim_code_id\n             LEFT JOIN batches b ON b.id = c.batch_id\n             LEFT JOIN redemptions rd ON rd.id = pl.redemption_id\n             LEFT JOIN rewards r ON r.id = rd.reward_id\n             WHERE pl.user_id = :user_id{$condition}\n             ORDER BY pl.id DESC LIMIT {$limit}"
        );
        $params = ['user_id' => $userId];
        if ($beforeId !== null) {
            $params['before_id'] = $beforeId;
        }
        $statement->execute($params);
        return $statement->fetchAll();
    }
}
