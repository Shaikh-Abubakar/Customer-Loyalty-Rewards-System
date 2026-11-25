<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\PointsLedgerRepository;
use PDO;

final class LedgerService
{
    private PointsLedgerRepository $ledger;

    public function __construct(PDO $pdo)
    {
        $this->ledger = new PointsLedgerRepository($pdo);
    }

    public function recordClaimCredit(int $userId, int $codeId, int $points): void
    {
        $this->ledger->insertClaim($userId, $codeId, $points);
    }

    public function recordRedemptionDebit(int $userId, int $redemptionId, int $pointsCost): void
    {
        $this->ledger->insertRedemption($userId, $redemptionId, $pointsCost);
    }

    public function balance(int $userId): int
    {
        return $this->ledger->balance($userId);
    }

    public function summary(int $userId): array
    {
        return $this->ledger->summary($userId);
    }

    public function recent(int $userId, int $limit = 5): array
    {
        return $this->ledger->recent($userId, $limit);
    }

    public function history(int $userId, ?int $beforeId = null, int $limit = 25): array
    {
        return $this->ledger->history($userId, $beforeId, $limit);
    }
}
