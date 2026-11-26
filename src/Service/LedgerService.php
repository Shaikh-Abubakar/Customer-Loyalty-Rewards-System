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

    public function addAdjustment(int $userId, int $delta, string $idempotencyKey, string $reason, int $adminId): void
    {
        if ($delta === 0 || $delta < -1000000 || $delta > 1000000 || trim($reason) === '') {
            throw new \InvalidArgumentException('The adjustment amount or reason is invalid.');
        }
        $this->ledger->insertAdjustment($userId, $delta, $idempotencyKey, trim($reason), $adminId);
    }

    public function reverseEntry(int $userId, int $entryId, string $idempotencyKey, string $reason, int $adminId): void
    {
        $original = $this->ledger->findEntry($entryId);
        if ($original === null || (int)$original['user_id'] !== $userId
            || !in_array($original['entry_type'], ['claim','adjustment'], true)
            || $original['reverses_entry_id'] !== null) {
            throw new \InvalidArgumentException('That points entry cannot be reversed.');
        }
        $this->ledger->insertReversal(
            $userId,
            $entryId,
            -((int)$original['points_delta']),
            $idempotencyKey,
            trim($reason),
            $adminId
        );
    }

    /** Must be called inside the pending-redemption cancellation transaction. */
    public function reverseRedemptionDebitWithinTransaction(int $userId, int $redemptionId, string $idempotencyKey, string $reason, int $adminId): void
    {
        $original = $this->ledger->findRedemptionDebit($redemptionId);
        if ($original === null || (int)$original['user_id'] !== $userId || (int)$original['points_delta'] >= 0) {
            throw new \RuntimeException('The original redemption debit is missing or inconsistent.');
        }
        $this->ledger->insertReversal(
            $userId,
            (int)$original['id'],
            -((int)$original['points_delta']),
            $idempotencyKey,
            trim($reason),
            $adminId
        );
    }

    public function redemptionDebit(int $redemptionId): ?array
    {
        return $this->ledger->findRedemptionDebit($redemptionId);
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
