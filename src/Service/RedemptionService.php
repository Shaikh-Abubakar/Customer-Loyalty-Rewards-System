<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\RedemptionRepository;
use App\Repository\AuditLogRepository;
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
    private AuditLogRepository $audit;

    public function __construct(private PDO $pdo)
    {
        $this->redemptions = new RedemptionRepository($pdo);
        $this->users = new UserRepository($pdo);
        $this->rewards = new RewardService($pdo);
        $this->ledger = new LedgerService($pdo);
        $this->audit = new AuditLogRepository($pdo);
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

    public function fulfil(int $redemptionId,int $adminId,string $note,?string $ip,?string $correlation): void
    {
        $note=trim($note);
        if ($redemptionId<1 || \App\Support\Http::textLength($note)>255) {
            throw new \InvalidArgumentException('The fulfilment note must be 255 characters or fewer.');
        }
        Database::transaction($this->pdo,function () use ($redemptionId,$adminId,$note,$ip,$correlation): void {
            $redemption=$this->redemptions->findForAdmin($redemptionId);
            if ($redemption===null || $redemption['status']!=='pending') {
                throw new RuntimeException('Only a pending redemption can be fulfilled.');
            }
            if (!$this->users->lockCustomerRow((int)$redemption['user_id'])) {
                throw new RuntimeException('The customer record could not be locked.');
            }
            // Re-read after acquiring the customer lock so competing admin actions serialize.
            $redemption=$this->redemptions->lockForAdmin($redemptionId);
            if ($redemption===null || $redemption['status']!=='pending' || !$this->redemptions->markFulfilled($redemptionId,$adminId,$note!==''?$note:null)) {
                throw new RuntimeException('This redemption has already been processed.');
            }
            $this->audit->record($adminId,'redemption_fulfilled','redemption',$redemptionId,null,
                'Fulfilled reward request for customer '.$redemption['user_id'].'.',$ip,$correlation);
        });
    }

    public function cancel(int $redemptionId,int $adminId,string $reason,?string $ip,?string $correlation): void
    {
        $reason=trim($reason);
        if ($redemptionId<1 || $reason==='' || \App\Support\Http::textLength($reason)>255) {
            throw new \InvalidArgumentException('Enter a cancellation reason of up to 255 characters.');
        }
        Database::transaction($this->pdo,function () use ($redemptionId,$adminId,$reason,$ip,$correlation): void {
            $redemption=$this->redemptions->findForAdmin($redemptionId);
            if ($redemption===null || $redemption['status']!=='pending') {
                throw new RuntimeException('Only a pending redemption can be cancelled.');
            }
            if (!$this->users->lockCustomerRow((int)$redemption['user_id'])) {
                throw new RuntimeException('The customer record could not be locked.');
            }
            $redemption=$this->redemptions->lockForAdmin($redemptionId);
            if ($redemption===null || $redemption['status']!=='pending') {
                throw new RuntimeException('This redemption has already been processed.');
            }
            $debit=$this->ledger->redemptionDebit((int)$redemptionId);
            if ($debit===null) {
                throw new RuntimeException('The original points debit is missing; cancellation was stopped for reconciliation.');
            }
            $this->ledger->reverseRedemptionDebitWithinTransaction((int)$redemption['user_id'],(int)$redemptionId,
                'redemption-cancel-'.$redemptionId,$reason,$adminId);
            $this->rewards->returnOneWithinTransaction((int)$redemption['reward_id']);
            if (!$this->redemptions->markCancelled($redemptionId,$adminId,$reason)) {
                throw new RuntimeException('The redemption status could not be changed.');
            }
            $this->audit->record($adminId,'redemption_cancelled','redemption',$redemptionId,$reason,
                'Cancelled pending redemption; points reversal and one stock unit returned.',$ip,$correlation);
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
