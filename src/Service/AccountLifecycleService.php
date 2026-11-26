<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\AccountStatusRepository;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Support\Database;
use App\Support\Http;
use PDO;
use RuntimeException;

final class AccountLifecycleService
{
    private UserRepository $users;
    private AccountStatusRepository $events;
    private AuditLogRepository $audit;

    public function __construct(private PDO $pdo)
    {
        $this->users=new UserRepository($pdo);
        $this->events=new AccountStatusRepository($pdo);
        $this->audit=new AuditLogRepository($pdo);
    }

    public function changeStatus(int $customerId,string $action,string $category,string $reason,int $adminId,?string $ip,?string $correlation): void
    {
        $category=trim($category); $reason=trim($reason);
        $categories=['fraud_review','abuse','customer_request','security_concern','legal_or_retention','admin_correction','other'];
        if (!in_array($action,['suspend','reactivate'],true) || !in_array($category,$categories,true)
            || $reason==='' || Http::textLength($reason)>500 || $customerId<1) {
            throw new \InvalidArgumentException('Choose an action and reason category, then enter a reason of up to 500 characters.');
        }
        Database::transaction($this->pdo,function () use ($customerId,$action,$category,$reason,$adminId,$ip,$correlation): void {
            $customer=$this->users->lockCustomerRow($customerId);
            if ($customer===null) { throw new RuntimeException('Customer not found.'); }
            $before=(string)$customer['status'];
            if ($action==='suspend') {
                if ($before!=='active') { throw new RuntimeException('Only an active customer can be suspended.'); }
                $after='suspended';
            } else {
                if ($before==='closed') {
                    $closure=$this->events->latestClosureRequest($customerId);
                    if ($closure===null || strtotime((string)$closure['created_at'].' UTC') < time()-30*86400) {
                        throw new RuntimeException('The 30-day account recovery period has ended.');
                    }
                } elseif ($before!=='suspended') {
                    throw new RuntimeException('Only a suspended or recoverable closed account can be reactivated.');
                }
                $after='active';
            }
            $this->users->changeCustomerStatus($customerId,$after);
            $this->events->add($customerId,$action,$category,$reason,$adminId);
            $this->audit->record($adminId,'customer_'.$action,'customer',$customerId,$reason,'Account status changed from '.$before.' to '.$after.'.',$ip,$correlation);
        });
    }
}
