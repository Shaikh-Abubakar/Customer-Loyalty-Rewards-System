<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class FraudEventRepository
{
    public function __construct(private PDO $pdo) {}

    public function existsRecent(string $eventType, ?int $userId, ?string $ip, int $windowSeconds): bool
    {
        $sql = 'SELECT id FROM fraud_events WHERE event_type = :event_type AND created_at >= :since';
        $params = [
            'event_type' => $eventType,
            'since' => gmdate('Y-m-d H:i:s', time() - $windowSeconds),
        ];
        if ($userId === null) {
            $sql .= ' AND user_id IS NULL';
        } else {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }
        if ($ip === null) {
            $sql .= ' AND ip_address IS NULL';
        } else {
            $sql .= ' AND ip_address = :ip';
            $params['ip'] = $ip;
        }
        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn() !== false;
    }

    public function record(
        string $eventType,
        string $severity,
        ?int $userId,
        ?string $ip,
        ?int $batchId,
        ?int $codeId,
        string $details
    ): void {
        $statement = $this->pdo->prepare(
            "INSERT INTO fraud_events\n             (event_type, severity, user_id, ip_address, batch_id, code_id, details, created_at)\n             VALUES (:event_type, :severity, :user_id, :ip, :batch_id, :code_id, :details, UTC_TIMESTAMP())"
        );
        $statement->execute([
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => $userId,
            'ip' => $ip,
            'batch_id' => $batchId,
            'code_id' => $codeId,
            'details' => $details,
        ]);
    }
}
