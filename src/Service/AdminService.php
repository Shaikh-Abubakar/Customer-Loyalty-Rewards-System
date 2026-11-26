<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\AdminRepository;
use App\Repository\AuditLogRepository;
use App\Repository\FraudEventRepository;
use App\Repository\UserRepository;
use App\Support\Database;
use App\Support\Http;
use PDO;
use RuntimeException;

final class AdminService
{
    private AdminRepository $read;
    private AuditLogRepository $audit;
    private FraudEventRepository $fraud;
    private UserRepository $users;
    private LedgerService $ledger;

    public function __construct(private PDO $pdo)
    {
        $this->read=new AdminRepository($pdo);
        $this->audit=new AuditLogRepository($pdo);
        $this->fraud=new FraudEventRepository($pdo);
        $this->users=new UserRepository($pdo);
        $this->ledger=new LedgerService($pdo);
    }

    public function dashboard(): array { return $this->read->dashboard(); }
    public function recentAdminActivity(): array { return $this->read->recentAdminActivity(); }
    public function recentLoyaltyActivity(): array { return $this->read->recentLoyaltyActivity(); }
    public function products(string $search='',?string $status=null): array { return $this->read->products($search,$status); }
    public function product(int $id): ?array { return $this->read->product($id); }
    public function batches(string $search='',?string $status=null): array { return $this->read->batches($search,$status); }
    public function batch(int $id): ?array { return $this->read->batch($id); }
    public function activeProducts(): array { return $this->read->activeProducts(); }
    public function customers(string $search='',?string $status=null): array { return $this->read->customers($search,$status); }
    public function rewards(): array { return $this->read->rewards(); }
    public function reward(int $id): ?array { return $this->read->reward($id); }
    public function redemptions(string $search='',?string $status=null): array { return $this->read->redemptions($search,$status); }
    public function redemption(int $id): ?array { return $this->read->redemption($id); }
    public function fraudEvents(?string $status='open',?string $severity=null,string $search='',?string $eventType=null): array { return $this->read->fraudEvents($status,$severity,$search,$eventType); }
    public function auditLogs(string $action='',string $entity='',string $search=''): array { return $this->read->auditLogs($action,$entity,$search); }
    public function reportClaimsByBatch(string $from,string $to): array { return $this->read->reportClaimsByBatch($from,$to); }
    public function reportPoints(string $from,string $to): array { return $this->read->reportPoints($from,$to); }
    public function reportRedemptions(string $from,string $to): array { return $this->read->reportRedemptions($from,$to); }
    public function reconciliationRuns(): array { return $this->read->reconciliationRuns(); }
    public function reconciliationFindings(?int $runId=null): array { return $this->read->reconciliationFindings($runId); }

    public function customer(int $id,int $adminId,?string $ip,?string $correlation): ?array
    {
        $customer=$this->read->customer($id);
        if ($customer!==null) {
            $this->audit->record($adminId,'customer_viewed','customer',$id,null,'Administrator viewed customer details.',$ip,$correlation);
        }
        return $customer;
    }

    public function customerLedger(int $id): array { return $this->read->customerLedger($id); }
    public function customerRedemptions(int $id): array { return $this->read->customerRedemptions($id); }
    public function customerStatusEvents(int $id): array { return $this->read->customerStatusEvents($id); }

    public function adjustPoints(int $customerId,int $delta,string $key,string $reason,int $adminId,?string $ip,?string $correlation): void
    {
        $reason=trim($reason);
        if ($customerId<1 || $delta===0 || $delta < -1000000 || $delta > 1000000
            || preg_match('/^[a-f0-9]{32}$/D',$key)!==1 || $reason==='' || Http::textLength($reason)>255) {
            throw new \InvalidArgumentException('Enter a non-zero adjustment between -1,000,000 and 1,000,000 and a reason.');
        }
        Database::transaction($this->pdo,function () use ($customerId,$delta,$key,$reason,$adminId,$ip,$correlation): void {
            if ($this->users->lockCustomerRow($customerId)===null) { throw new RuntimeException('Customer not found.'); }
            $this->ledger->addAdjustment($customerId,$delta,$key,$reason,$adminId);
            $this->audit->record($adminId,'points_adjusted','customer',$customerId,$reason,'Adjustment delta: '.$delta.'.',$ip,$correlation);
        });
    }

    public function reversePoints(int $customerId,int $entryId,string $key,string $reason,int $adminId,?string $ip,?string $correlation): void
    {
        $reason=trim($reason);
        if ($customerId<1 || $entryId<1 || preg_match('/^[a-f0-9]{32}$/D',$key)!==1 || $reason==='' || Http::textLength($reason)>255) {
            throw new \InvalidArgumentException('Select a ledger entry and provide a reason.');
        }
        Database::transaction($this->pdo,function () use ($customerId,$entryId,$key,$reason,$adminId,$ip,$correlation): void {
            if ($this->users->lockCustomerRow($customerId)===null) { throw new RuntimeException('Customer not found.'); }
            $this->ledger->reverseEntry($customerId,$entryId,$key,$reason,$adminId);
            $this->audit->record($adminId,'points_entry_reversed','customer',$customerId,$reason,'Reversed points ledger entry '.$entryId.'.',$ip,$correlation);
        });
    }

    public function reviewFraud(int $eventId,string $status,string $note,int $adminId,?string $ip,?string $correlation): void
    {
        $note=trim($note);
        if (!in_array($status,['reviewed','dismissed','escalated'],true) || $note==='' || Http::textLength($note)>500) {
            throw new \InvalidArgumentException('Choose a review outcome and enter a note (maximum 500 characters).');
        }
        Database::transaction($this->pdo,function () use ($eventId,$status,$note,$adminId,$ip,$correlation): void {
            if (!$this->fraud->review($eventId,$status,$note,$adminId)) { throw new RuntimeException('This fraud event is no longer open. Refresh the queue.'); }
            $this->audit->record($adminId,'fraud_event_'.$status,'fraud_event',$eventId,$note,'Fraud event reviewed.',$ip,$correlation);
        });
    }
}
