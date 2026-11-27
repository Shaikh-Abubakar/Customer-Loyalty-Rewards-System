<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\ClaimAttemptRepository;
use App\Repository\CodesRepository;
use App\Repository\UserRepository;
use App\Support\CodeFormat;
use App\Support\Database;
use App\Support\RateLimitGuard;
use PDO;

final class ClaimService
{
    private ClaimAttemptRepository $attempts;
    private CodesRepository $codes;
    private UserRepository $users;
    private CodeVerifierService $verifier;
    private LedgerService $ledger;
    private FraudService $fraud;

    public function __construct(private PDO $pdo)
    {
        $this->attempts = new ClaimAttemptRepository($pdo);
        $this->codes = new CodesRepository($pdo);
        $this->users = new UserRepository($pdo);
        $this->verifier = new CodeVerifierService($pdo);
        $this->ledger = new LedgerService($pdo);
        $this->fraud = new FraudService($pdo);
    }

    /** @return array{status:string,points?:int,balance?:int} */
    public function claim(int $userId, string $input, string $ip): array
    {
        return RateLimitGuard::run($this->pdo, [
            ['scope' => 'claim-user', 'subject' => (string) $userId],
            ['scope' => 'claim-ip', 'subject' => $ip],
        ], fn (): array => $this->claimWithinLimit($userId, $input, $ip));
    }

    /** @return array{status:string,points?:int,balance?:int} */
    private function claimWithinLimit(int $userId, string $input, string $ip): array
    {
        $limits = app_config('rate_limits', []);
        $failureLimit = max(1, (int) ($limits['claim_failures'] ?? 10));
        $failureWindow = max(60, (int) ($limits['claim_window_seconds'] ?? 900));

        $userFailures = $this->attempts->countFailuresByUser($userId, $failureWindow);
        $ipFailures = $this->attempts->countFailuresByIp($ip, $failureWindow);
        if ($userFailures >= $failureLimit || $ipFailures >= $failureLimit) {
            $this->attempts->record($userId, $ip, 'rate_limited');
            $this->fraud->recordOnce(
                'claim_rate_exceeded', 'warning', $userId, $ip, null, null,
                'Claim attempts exceeded the configured failure threshold.', $failureWindow
            );
            return ['status' => 'rate_limited'];
        }

        $normalised = CodeFormat::normalize($input);
        if (!CodeFormat::isValid($normalised)) {
            $this->recordFailure($userId, $ip, 'invalid_format', null, null, $failureLimit, $failureWindow);
            return ['status' => 'invalid_format'];
        }

        $code = $this->verifier->findByPlaintext($normalised);
        if ($code === null) {
            $this->recordFailure($userId, $ip, 'not_found', null, null, $failureLimit, $failureWindow);
            return ['status' => 'not_found'];
        }

        $codeId = (int) $code['id'];
        $batchId = (int) $code['batch_id'];
        if ($code['status'] === 'claimed') {
            $this->recordFailure($userId, $ip, 'already_claimed', $codeId, $batchId, $failureLimit, $failureWindow);
            return [
                'status' => ((int) ($code['claimed_by_user_id'] ?? 0) === $userId)
                    ? 'already_claimed_by_you'
                    : 'generic_failure',
            ];
        }
        if ($code['status'] === 'voided') {
            $this->recordFailure($userId, $ip, 'code_voided', $codeId, $batchId, $failureLimit, $failureWindow);
            $this->fraud->recordOnce(
                'voided_batch_code_claimed', 'warning', $userId, $ip, $batchId, $codeId,
                'A claim was attempted for a voided product code.'
            );
            return ['status' => 'generic_failure'];
        }

        try {
            $points = Database::transaction($this->pdo, function () use ($userId, $codeId, $batchId, $ip): int {
                if (!$this->users->lockActiveCustomer($userId)) {
                    throw new ClaimRejected('account_inactive');
                }
                $batch = $this->codes->lockBatch($batchId);
                if ($batch === null || $batch['status'] !== 'active') {
                    throw new ClaimRejected('batch_inactive');
                }
                if (!$this->codes->markClaimed($codeId, $userId)) {
                    throw new ClaimRejected('claim_race');
                }
                $points = (int) $batch['points_per_code'];
                if ($points < 1 || $points > 10000) {
                    throw new \RuntimeException('The product batch has an invalid points value.');
                }
                $this->ledger->recordClaimCredit($userId, $codeId, $points);
                $this->attempts->record($userId, $ip, 'success', $codeId);
                return $points;
            });
        } catch (ClaimRejected $rejected) {
            if ($rejected->outcome === 'account_inactive') {
                return ['status' => 'account_inactive'];
            }
            $state = $this->codes->findClaimState($codeId);
            if (is_array($state) && $state['status'] === 'claimed'
                && (int) ($state['claimed_by_user_id'] ?? 0) === $userId) {
                $this->recordFailure($userId, $ip, 'already_claimed', $codeId, $batchId, $failureLimit, $failureWindow);
                return ['status' => 'already_claimed_by_you'];
            }
            if ($rejected->outcome === 'batch_inactive') {
                $this->recordFailure($userId, $ip, 'batch_inactive', $codeId, $batchId, $failureLimit, $failureWindow);
                // A whole-batch void leaves unclaimed code rows in their existing state;
                // detect the batch state separately so the documented fraud signal is raised.
                if ($this->codes->findBatchStatus($batchId) === 'voided') {
                    $this->fraud->recordOnce(
                        'voided_batch_code_claimed', 'warning', $userId, $ip, $batchId, $codeId,
                        'A claim was attempted for a code from a voided batch.'
                    );
                }
            } elseif (is_array($state) && $state['status'] === 'voided') {
                $this->recordFailure($userId, $ip, 'code_voided', $codeId, $batchId, $failureLimit, $failureWindow);
                $this->fraud->recordOnce(
                    'voided_batch_code_claimed', 'warning', $userId, $ip, $batchId, $codeId,
                    'A claim was attempted for a voided product code.'
                );
            } else {
                $this->recordFailure($userId, $ip, 'already_claimed', $codeId, $batchId, $failureLimit, $failureWindow);
            }
            return ['status' => 'generic_failure'];
        }

        $balance = $this->ledger->balance($userId);
        $successfulClaims = $this->attempts->countSuccessfulByUser(
            $userId,
            max(60, (int) ($limits['successful_claim_window_seconds'] ?? 86400))
        );
        $successfulThreshold = max(1, (int) ($limits['successful_claims_per_user'] ?? 20));
        if ($successfulClaims >= $successfulThreshold) {
            $this->fraud->recordOnce(
                'claim_rate_exceeded', 'warning', $userId, $ip, $batchId, $codeId,
                'The customer reached the configured successful-claim review threshold.', 86400
            );
        }

        $distinctWindow = max(60, (int) ($limits['distinct_accounts_window_seconds'] ?? 3600));
        $distinctThreshold = max(1, (int) ($limits['distinct_accounts_per_ip'] ?? 10));
        $distinctUsers = $this->attempts->countDistinctSuccessfulUsersByIp($ip, $distinctWindow);
        if ($distinctUsers >= $distinctThreshold) {
            $this->fraud->recordOnce(
                'many_accounts_one_ip', 'warning', null, $ip, $batchId, $codeId,
                'The configured number of distinct customer accounts claimed from one IP address.', $distinctWindow
            );
        }

        return ['status' => 'success', 'points' => $points, 'balance' => $balance];
    }

    private function recordFailure(
        int $userId,
        string $ip,
        string $outcome,
        ?int $codeId,
        ?int $batchId,
        int $limit,
        int $window
    ): void {
        $this->attempts->record($userId, $ip, $outcome, $codeId);
        $userFailures = $this->attempts->countFailuresByUser($userId, $window);
        $ipFailures = $this->attempts->countFailuresByIp($ip, $window);
        if ($userFailures >= $limit || $ipFailures >= $limit) {
            $this->fraud->recordOnce(
                'repeated_invalid_codes', 'warning', $userId, $ip, $batchId, $codeId,
                'Repeated unsuccessful product-code claim attempts reached the configured threshold.', $window
            );
        }
    }
}
