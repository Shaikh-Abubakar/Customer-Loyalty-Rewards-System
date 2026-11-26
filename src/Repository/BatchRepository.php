<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class BatchRepository
{
    public function __construct(private PDO $pdo) {}

    public function activeProduct(int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id,sku,name,default_points FROM products WHERE id=:id AND is_active=1 LIMIT 1');
        $stmt->execute(['id'=>$productId]);
        $row=$stmt->fetch();
        return is_array($row)?$row:null;
    }

    public function create(string $batchCode, int $productId, int $points, int $quantity, int $adminId): int
    {
        $stmt=$this->pdo->prepare(
            "INSERT INTO batches (batch_code,product_id,points_per_code,planned_quantity,generated_count,status,created_by_user_id,created_at,updated_at)
             VALUES (:code,:product,:points,:quantity,NULL,'draft',:admin,UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $stmt->execute(['code'=>$batchCode,'product'=>$productId,'points'=>$points,'quantity'=>$quantity,'admin'=>$adminId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function lock(int $id): ?array
    {
        $stmt=$this->pdo->prepare('SELECT id,batch_code,product_id,points_per_code,planned_quantity,generated_count,status FROM batches WHERE id=:id FOR UPDATE');
        $stmt->execute(['id'=>$id]);
        $row=$stmt->fetch();
        return is_array($row)?$row:null;
    }

    public function findWithProduct(int $id): ?array
    {
        $stmt=$this->pdo->prepare(
            'SELECT b.*,p.sku,p.name AS product_name FROM batches b INNER JOIN products p ON p.id=b.product_id WHERE b.id=:id LIMIT 1'
        );
        $stmt->execute(['id'=>$id]);
        $row=$stmt->fetch();
        return is_array($row)?$row:null;
    }

    public function setGenerated(int $id, int $count): bool
    {
        $stmt=$this->pdo->prepare("UPDATE batches SET status='generated',generated_count=:count,generated_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND status='draft'");
        $stmt->execute(['count'=>$count,'id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function markExported(int $id): bool
    {
        $stmt=$this->pdo->prepare("UPDATE batches SET status='exported',exported_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND status='generated'");
        $stmt->execute(['id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function markPrinted(int $id): bool
    {
        $stmt=$this->pdo->prepare("UPDATE batches SET status='printed',printed_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND status='exported'");
        $stmt->execute(['id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function activate(int $id, int $adminId): bool
    {
        $stmt=$this->pdo->prepare("UPDATE batches SET status='active',activated_at=UTC_TIMESTAMP(),activated_by_user_id=:admin,updated_at=UTC_TIMESTAMP() WHERE id=:id AND status='printed'");
        $stmt->execute(['admin'=>$adminId,'id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function void(int $id, int $adminId, string $reason): bool
    {
        $stmt=$this->pdo->prepare("UPDATE batches SET status='voided',voided_at=UTC_TIMESTAMP(),voided_by_user_id=:admin,void_reason=:reason,updated_at=UTC_TIMESTAMP() WHERE id=:id AND status<>'voided'");
        $stmt->execute(['admin'=>$adminId,'reason'=>$reason,'id'=>$id]);
        return $stmt->rowCount()===1;
    }

    public function hasExport(int $batchId): bool
    {
        $stmt=$this->pdo->prepare('SELECT id FROM code_exports WHERE batch_id=:id LIMIT 1');
        $stmt->execute(['id'=>$batchId]);
        return $stmt->fetchColumn()!==false;
    }

    public function hasRecordedExport(int $batchId): bool
    {
        $stmt=$this->pdo->prepare("SELECT id FROM code_exports WHERE batch_id=:id AND status='recorded' LIMIT 1");
        $stmt->execute(['id'=>$batchId]);
        return $stmt->fetchColumn()!==false;
    }

    public function recordExport(int $batchId,int $adminId,int $count,string $status,?string $ip,?string $correlation): void
    {
        $stmt=$this->pdo->prepare(
            'INSERT INTO code_exports (batch_id,exported_by_user_id,code_count,status,ip_address,correlation_id,exported_at)
             VALUES (:batch,:admin,:count,:status,:ip,:correlation,UTC_TIMESTAMP())'
        );
        $stmt->execute(['batch'=>$batchId,'admin'=>$adminId,'count'=>$count,'status'=>$status,'ip'=>$ip,'correlation'=>$correlation]);
    }

    public function updateExportStatus(int $batchId,string $status): void
    {
        $stmt=$this->pdo->prepare('UPDATE code_exports SET status=:status WHERE batch_id=:batch');
        $stmt->execute(['status'=>$status,'batch'=>$batchId]);
    }
}
