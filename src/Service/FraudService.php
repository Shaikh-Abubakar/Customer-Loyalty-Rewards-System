<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\FraudEventRepository;
use PDO;

final class FraudService
{
    private FraudEventRepository $events;

    public function __construct(PDO $pdo)
    {
        $this->events = new FraudEventRepository($pdo);
    }

    public function recordOnce(
        string $type,
        string $severity,
        ?int $userId,
        ?string $ip,
        ?int $batchId,
        ?int $codeId,
        string $details,
        int $windowSeconds = 900
    ): void {
        if (!$this->events->existsRecent($type, $userId, $ip, $windowSeconds)) {
            $this->events->record($type, $severity, $userId, $ip, $batchId, $codeId, $details);
        }
    }
}
