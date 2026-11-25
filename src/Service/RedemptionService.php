<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\RedemptionRepository;
use App\Repository\UserRepository;
use App\Support\Database;
use PDO;
use RuntimeException;

final class RedemptionService
{
    private RedemptionRepository $redemptions;
    private UserRepository $users;
    private RewardService $rewards;
    private LedgerService $ledger;

    public function __construct(private PDO $pdo)
    {
        $this->redemptions = new RedemptionRepository($pdo);
        $this->users = new UserRepository($pdo);
        $this->rewards = new RewardService($pdo);
        $this->ledger = new LedgerService($pdo);
    }

    /** @return array<string,mixed> */
    public function request(int $userId, int $rewardId, string $token): array
    {
        if (preg_match('/^[a-f0-9]{32}$/D', $token) !== 1) {
            return ['status' => 'invalid_request'];
        }

        $existing = $this->redemptions->findByUserAndToken($userId, $token);
        if ($existing !== null) {
            return $this->existingResult($existing, $userId);
        }

        return Database::transaction($this->pdo, function () use ($userId, $rewardId, $token): array {
            if (!$this->users->lockActiveCustomer($userId)) {
                return ['status' => 'account_inactive'];
            }

            // The per-customer lock serialises retries using the same idempotency token.
            $existing = $this->redemptions->findByUserAndToken($userId, $token);
            if ($existing !== null) {
                return $this->existingResult($existing, $userId);
            }

            $reward = $this->rewards->findForRedemptionWithinTransaction($rewardId);
            if ($reward === null || (int) $reward['is_active'] !== 1) {
                return ['status' => 'unavailable'];
            }
            $cost = (int) $reward['points_cost'];
            if ($cost < 1 || $cost > 1000000) {
                throw new RuntimeException('The reward has an invalid points cost.');
            }
            $balance = $this->ledger->balance($userId);
            if ($balance < $cost) {
                return ['status' => 'insufficient_points', 'balance' => $balance, 'cost' => $cost];
            }
            if (!$this->rewards->reserveOneWithinTransaction($rewardId)) {
                $currentReward = $this->rewards->find($rewardId);
                if ($currentReward === null || (int) $currentReward['is_active'] !== 1) {
                    return ['status' => 'unavailable'];
                }
                return ['status' => 'out_of_stock'];
            }

            $redemptionId = $this->redemptions->create($userId, $rewardId, $cost, $token);
            $this->ledger->recordRedemptionDebit($userId, $redemptionId, $cost);
            return [
                'status' => 'success',
                'redemption_id' => $redemptionId,
                'points_cost' => $cost,
                'balance' => $balance - $cost,
                'reward_name' => (string) $reward['name'],
            ];
        });
    }

    public function recent(int $userId, int $limit = 5): array
    {
        return $this->redemptions->recentByUser($userId, $limit);
    }

    public function all(int $userId): array
    {
        return $this->redemptions->allByUser($userId);
    }

    private function existingResult(array $existing, int $userId): array
    {
        return [
            'status' => 'duplicate',
            'redemption_id' => (int) $existing['id'],
            'points_cost' => (int) $existing['points_cost'],
            'balance' => $this->ledger->balance($userId),
            'redemption_status' => (string) $existing['status'],
        ];
    }
}
