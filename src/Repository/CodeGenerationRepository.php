<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CodeGenerationRepository
{
    public function __construct(private PDO $pdo) {}

    public function existsHash(string $hash, int $keyId): bool
    {
        $stmt=$this->pdo->prepare('SELECT id FROM codes WHERE code_hash=:hash AND key_id=:key_id LIMIT 1');
        $stmt->bindValue(':hash',$hash,PDO::PARAM_LOB);
        $stmt->bindValue(':key_id',$keyId,PDO::PARAM_INT);
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
