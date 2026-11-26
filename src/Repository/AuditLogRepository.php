<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class AuditLogRepository
{
    public function __construct(private PDO $pdo) {}

    public function record(?int $actorId, string $action, string $entityType, ?int $entityId, ?string $reason, ?string $details, ?string $ip, ?string $correlationId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (actor_user_id,action,entity_type,entity_id,reason,details,ip_address,correlation_id,created_at)
             VALUES (:actor,:action,:entity,:entity_id,:reason,:details,:ip,:correlation,UTC_TIMESTAMP())'
        );
        $stmt->execute([
            'actor' => $actorId, 'action' => $action, 'entity' => $entityType, 'entity_id' => $entityId,
            'reason' => $reason, 'details' => $details, 'ip' => $ip, 'correlation' => $correlationId,
        ]);
    }
}
