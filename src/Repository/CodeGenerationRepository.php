<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CodeGenerationRepository
{
    public function __construct(private PDO $pdo) {}

    public function existsHash(string $hash): bool
    {
        // code_hash is globally unique in the schema, regardless of key_id.
        // Check across all key versions so a cross-key collision is rejected
        // before the generation transaction attempts an insert.
        $stmt=$this->pdo->prepare('SELECT id FROM codes WHERE code_hash=:hash LIMIT 1');
        $stmt->bindValue(':hash',$hash,PDO::PARAM_LOB);
        $stmt->execute();
        return $stmt->fetchColumn()!==false;
    }

    public function insert(int $batchId,int $keyId,string $hash): void
    {
        $stmt=$this->pdo->prepare("INSERT INTO codes (batch_id,key_id,code_hash,status,created_at) VALUES (:batch,:key_id,:hash,'unclaimed',UTC_TIMESTAMP())");
        $stmt->bindValue(':batch',$batchId,PDO::PARAM_INT);
        $stmt->bindValue(':key_id',$keyId,PDO::PARAM_INT);
        $stmt->bindValue(':hash',$hash,PDO::PARAM_LOB);
        $stmt->execute();
    }
}
