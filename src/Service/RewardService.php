<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\RewardRepository;
use PDO;

final class RewardService
{
    private RewardRepository $rewards;

    public function __construct(PDO $pdo)
    {
        $this->rewards = new RewardRepository($pdo);
    }

    public function activeCatalogue(): array
    {
        return $this->rewards->findActive();
    }

    public function find(int $rewardId): ?array
    {
        return $this->rewards->findById($rewardId);
    }

    /** Lock a reward so its price and active state cannot change during redemption. */
    public function findForRedemptionWithinTransaction(int $rewardId): ?array
    {
        return $this->rewards->findByIdForUpdate($rewardId);
    }

    /** Must be called inside the RedemptionService transaction. */
    public function reserveOneWithinTransaction(int $rewardId): bool
    {
        return $this->rewards->reserveOne($rewardId);
    }
}
