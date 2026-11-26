<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ReconciliationRepository
{
    public function __construct(private PDO $pdo) {}

    public function scopeExists(string $scope,int $scopeId): bool
    {
        if ($scope==='customer') {
            $stmt=$this->pdo->prepare("SELECT id FROM users WHERE id=:id AND role='customer' LIMIT 1");
        } elseif ($scope==='batch') {
            $stmt=$this->pdo->prepare('SELECT id FROM batches WHERE id=:id LIMIT 1');
        } else {
            return $scope==='all';
        }
        $stmt->execute(['id'=>$scopeId]);
        return $stmt->fetchColumn()!==false;
    }

    public function start(int $adminId,string $scope,?int $scopeId): int
    {
        $stmt=$this->pdo->prepare("INSERT INTO reconciliation_runs (run_by_user_id,scope,scope_ref_id,outcome,discrepancy_count,started_at) VALUES (:admin,:scope,:scope_id,'failed',0,UTC_TIMESTAMP())");
        $stmt->execute(['admin'=>$adminId,'scope'=>$scope,'scope_id'=>$scopeId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function addFinding(int $runId,string $checkType,?int $userId,?int $ledgerId,?int $expected,?int $actual,string $note): void
    {
        $stmt=$this->pdo->prepare('INSERT INTO reconciliation_findings (run_id,check_type,user_id,ledger_entry_id,expected_value,actual_value,status,note) VALUES (:run,:type,:user,:ledger,:expected,:actual,\'open\',:note)');
        $stmt->execute(['run'=>$runId,'type'=>$checkType,'user'=>$userId,'ledger'=>$ledgerId,'expected'=>$expected,'actual'=>$actual,'note'=>$note]);
    }

    public function finish(int $runId,string $outcome,int $count): void
    {
        $stmt=$this->pdo->prepare('UPDATE reconciliation_runs SET outcome=:outcome,discrepancy_count=:count,finished_at=UTC_TIMESTAMP() WHERE id=:id');
        $stmt->execute(['outcome'=>$outcome,'count'=>$count,'id'=>$runId]);
    }
}
