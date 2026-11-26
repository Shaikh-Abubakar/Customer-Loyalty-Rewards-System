<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\AuditLogRepository;
use App\Repository\ReconciliationRepository;
use App\Support\Database;
use PDO;
use Throwable;

final class ReconciliationService
{
    private ReconciliationRepository $runs;
    private AuditLogRepository $audit;

    public function __construct(private PDO $pdo)
    {
        $this->runs=new ReconciliationRepository($pdo);
        $this->audit=new AuditLogRepository($pdo);
    }

    public function run(string $scope,?int $scopeId,int $adminId,?string $ip,?string $correlation): array
    {
        if (!in_array($scope,['all','customer','batch'],true) || (($scope==='all') !== ($scopeId===null)) || ($scopeId!==null && $scopeId<1)) {
            throw new \InvalidArgumentException('Choose a valid reconciliation scope.');
        }
        if ($scopeId!==null && !$this->runs->scopeExists($scope,$scopeId)) {
            throw new \InvalidArgumentException('The selected customer or batch does not exist. Refresh the scope list and try again.');
        }
        try {
            return Database::transaction($this->pdo,function () use ($scope,$scopeId,$adminId,$ip,$correlation): array {
                $runId=$this->runs->start($adminId,$scope,$scopeId);
                $count=0;
                $count += $this->checkClaimLedger($runId,$scope,$scopeId);
                $count += $this->checkRedemptionDebits($runId,$scope,$scopeId);
                $count += $this->checkReversalLinks($runId,$scope,$scopeId);
                $count += $this->checkCustomerTotals($runId,$scope,$scopeId);
                $outcome=$count===0?'clean':'discrepancies';
                $this->runs->finish($runId,$outcome,$count);
                $this->audit->record($adminId,'reconciliation_run','reconciliation_run',$runId,null,
                    'Completed '.$scope.' reconciliation; findings='.$count.'; outcome='.$outcome.'.',$ip,$correlation);
                return ['run_id'=>$runId,'outcome'=>$outcome,'count'=>$count];
            });
        } catch (Throwable $exception) {
            try {
                Database::transaction($this->pdo,function () use ($scope,$scopeId,$adminId,$ip,$correlation): void {
                    $runId=$this->runs->start($adminId,$scope,$scopeId);
                    $this->runs->finish($runId,'failed',0);
                    $this->audit->record($adminId,'reconciliation_failed','reconciliation_run',$runId,'Run failed.','Reconciliation failed; technical details are in the server log.',$ip,$correlation);
                });
            } catch (Throwable) {}
            throw $exception;
        }
    }

    private function checkClaimLedger(int $runId,string $scope,?int $scopeId): int
    {
        $where="pl.entry_type='claim'";
        $params=[];
        if ($scope==='customer') { $where.=' AND pl.user_id=:scope_id'; $params['scope_id']=$scopeId; }
        if ($scope==='batch') { $where.=' AND c.batch_id=:scope_id'; $params['scope_id']=$scopeId; }
        $stmt=$this->pdo->prepare(
            "SELECT pl.id,pl.user_id,pl.points_delta,c.id AS code_id,c.status,c.claimed_by_user_id,b.points_per_code
             FROM points_ledger pl LEFT JOIN codes c ON c.id=pl.claim_code_id LEFT JOIN batches b ON b.id=c.batch_id
             WHERE {$where} AND (c.id IS NULL OR c.status<>'claimed' OR c.claimed_by_user_id IS NULL OR c.claimed_by_user_id<>pl.user_id OR b.points_per_code<>pl.points_delta)"
        );
        $stmt->execute($params);
        $rows=$stmt->fetchAll();
        foreach ($rows as $r) {
            $this->runs->addFinding($runId,'claim_vs_code',(int)$r['user_id'],(int)$r['id'],(int)($r['points_per_code']??0),(int)$r['points_delta'],'Claim credit does not match a claimed code, owner or batch points value.');
        }

        // Reverse direction: every claimed code must have exactly its corresponding ledger credit.
        $codeWhere="c.status='claimed'";
        $codeParams=[];
        if ($scope==='customer') { $codeWhere.=' AND c.claimed_by_user_id=:scope_id'; $codeParams['scope_id']=$scopeId; }
        if ($scope==='batch') { $codeWhere.=' AND c.batch_id=:scope_id'; $codeParams['scope_id']=$scopeId; }
        $codeStmt=$this->pdo->prepare(
            "SELECT c.id,c.claimed_by_user_id,b.points_per_code,pl.id AS ledger_id,pl.points_delta
             FROM codes c INNER JOIN batches b ON b.id=c.batch_id
             LEFT JOIN points_ledger pl ON pl.claim_code_id=c.id AND pl.entry_type='claim'
             WHERE {$codeWhere} AND (pl.id IS NULL OR c.claimed_by_user_id IS NULL OR pl.user_id<>c.claimed_by_user_id OR pl.points_delta<>b.points_per_code)"
        );
        $codeStmt->execute($codeParams);
        $missing=$codeStmt->fetchAll();
        foreach ($missing as $r) {
            $this->runs->addFinding($runId,'claim_vs_code',$r['claimed_by_user_id']===null?null:(int)$r['claimed_by_user_id'],
                isset($r['ledger_id'])?(int)$r['ledger_id']:null,(int)$r['points_per_code'],(int)($r['points_delta']??0),
                'Claimed code is missing its matching customer ledger credit.');
        }
        return count($rows)+count($missing);
    }

    private function checkRedemptionDebits(int $runId,string $scope,?int $scopeId): int
    {
        $where='1=1'; $params=[];
        if ($scope==='customer') { $where.=' AND rd.user_id=:scope_id'; $params['scope_id']=$scopeId; }
        if ($scope==='batch') { return 0; }
        $stmt=$this->pdo->prepare(
            "SELECT rd.id,rd.user_id,rd.points_cost,pl.id AS ledger_id,pl.points_delta
             FROM redemptions rd LEFT JOIN points_ledger pl ON pl.redemption_id=rd.id AND pl.entry_type='redemption'
             WHERE {$where} AND (pl.id IS NULL OR pl.points_delta<>-rd.points_cost)"
        );
        $stmt->execute($params); $rows=$stmt->fetchAll();
        foreach ($rows as $r) {
            $this->runs->addFinding($runId,'redemption_vs_debit',(int)$r['user_id'],isset($r['ledger_id'])?(int)$r['ledger_id']:null,-(int)$r['points_cost'],(int)($r['points_delta']??0),'Redemption is missing its exact points debit.');
        }
        return count($rows);
    }

    private function checkReversalLinks(int $runId,string $scope,?int $scopeId): int
    {
        $where="rev.entry_type='reversal'"; $params=[];
        if ($scope==='customer') { $where.=' AND rev.user_id=:scope_id'; $params['scope_id']=$scopeId; }
        if ($scope==='batch') { return 0; }
        $stmt=$this->pdo->prepare(
            "SELECT rev.id,rev.user_id,rev.points_delta,orig.points_delta AS original_delta,orig.entry_type AS original_type
             FROM points_ledger rev LEFT JOIN points_ledger orig ON orig.id=rev.reverses_entry_id
             WHERE {$where} AND (orig.id IS NULL OR orig.entry_type='reversal' OR orig.user_id IS NULL OR orig.user_id<>rev.user_id OR rev.points_delta<>-orig.points_delta)"
        );
        $stmt->execute($params); $rows=$stmt->fetchAll();
        foreach ($rows as $r) {
            $this->runs->addFinding($runId,'reversal_link',(int)$r['user_id'],(int)$r['id'],-(int)($r['original_delta']??0),(int)$r['points_delta'],'Reversal link is missing or does not exactly compensate the original entry.');
        }
        $where2="rd.status='cancelled'"; $params2=[];
        if ($scope==='customer') { $where2.=' AND rd.user_id=:scope_id'; $params2['scope_id']=$scopeId; }
        $stmt=$this->pdo->prepare(
            "SELECT rd.id,rd.user_id,debit.id AS debit_id,rev.id AS reversal_id
             FROM redemptions rd
             LEFT JOIN points_ledger debit ON debit.redemption_id=rd.id AND debit.entry_type='redemption'
             LEFT JOIN points_ledger rev ON rev.reverses_entry_id=debit.id AND rev.entry_type='reversal'
             WHERE {$where2} AND (debit.id IS NULL OR rev.id IS NULL)"
        );
        $stmt->execute($params2); $cancelled=$stmt->fetchAll();
        foreach ($cancelled as $r) {
            $this->runs->addFinding($runId,'reversal_link',(int)$r['user_id'],isset($r['debit_id'])?(int)$r['debit_id']:null,1,isset($r['reversal_id'])?1:0,'Cancelled redemption must have its original debit and linked points reversal.');
        }
        return count($rows)+count($cancelled);
    }

    private function checkCustomerTotals(int $runId,string $scope,?int $scopeId): int
    {
        if ($scope==='batch') { return 0; }
        $customerFilter=$scope==='customer'?' AND u.id=:scope_id':'';
        $params=$scope==='customer'?['scope_id'=>$scopeId]:[];
        $stmt=$this->pdo->prepare(
            "SELECT u.id,
                    COALESCE(ledger.total_points,0) AS ledger_total,
                    COALESCE(claims.claim_points,0)+COALESCE(redemption_report.redemption_points,0)
                      +COALESCE(adjustments.adjustment_points,0)+COALESCE(reversals.reversal_points,0) AS report_total
             FROM users u
             LEFT JOIN (SELECT user_id,SUM(points_delta) AS total_points FROM points_ledger GROUP BY user_id) ledger
               ON ledger.user_id=u.id
             LEFT JOIN (SELECT c.claimed_by_user_id AS user_id,SUM(b.points_per_code) AS claim_points
                        FROM codes c INNER JOIN batches b ON b.id=c.batch_id WHERE c.status='claimed' GROUP BY c.claimed_by_user_id) claims
               ON claims.user_id=u.id
             LEFT JOIN (SELECT user_id,SUM(CASE WHEN status IN ('pending','fulfilled') THEN -points_cost ELSE 0 END) AS redemption_points
                        FROM redemptions GROUP BY user_id) redemption_report ON redemption_report.user_id=u.id
             LEFT JOIN (SELECT user_id,SUM(points_delta) AS adjustment_points FROM points_ledger WHERE entry_type='adjustment' GROUP BY user_id) adjustments
               ON adjustments.user_id=u.id
             LEFT JOIN (SELECT rev.user_id,SUM(CASE WHEN rd.status='cancelled' THEN 0 ELSE rev.points_delta END) AS reversal_points
                        FROM points_ledger rev
                        LEFT JOIN points_ledger original ON original.id=rev.reverses_entry_id
                        LEFT JOIN redemptions rd ON rd.id=original.redemption_id
                        WHERE rev.entry_type='reversal' GROUP BY rev.user_id) reversals ON reversals.user_id=u.id
             WHERE u.role='customer'{$customerFilter}
             HAVING ledger_total<>report_total"
        );
        $stmt->execute($params); $rows=$stmt->fetchAll();
        foreach ($rows as $r) {
            $this->runs->addFinding($runId,'balance_vs_report',(int)$r['id'],null,(int)$r['report_total'],(int)$r['ledger_total'],
                'Ledger balance differs from the independently derived customer report total (claimed codes, open/fulfilled redemptions, adjustments and applicable reversals).');
        }
        return count($rows);
    }
}
