<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\RewardRepository;
use App\Repository\AuditLogRepository;
use App\Support\Database;
use PDO;

final class RewardService
{
    private RewardRepository $rewards;
    private AuditLogRepository $audit;

    public function __construct(PDO $pdo)
    {
        $this->rewards = new RewardRepository($pdo);
        $this->audit = new AuditLogRepository($pdo);
    }

    public function createAdmin(string $name, ?string $description, int $cost, int $stock, int $adminId, ?string $ip, ?string $correlation): int
    {
        $name = trim($name);
        if ($name === '' || \App\Support\Http::textLength($name) > 150 || $cost < 1 || $cost > 1000000 || $stock < 0 || $stock > 1000000000
            || ($description !== null && \App\Support\Http::textLength($description) > 5000)) {
            throw new \InvalidArgumentException('Enter a valid reward name, points cost and stock quantity.');
        }
        return Database::transaction($this->pdo, function () use ($name,$description,$cost,$stock,$adminId,$ip,$correlation): int {
            $id=$this->rewards->create($name, $description, $cost, $stock);
            $details=json_encode(['name'=>$name,'points_cost'=>$cost,'initial_stock'=>$stock],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $this->audit->record($adminId,'reward_created','reward',$id,null,$details?:'Created reward.', $ip,$correlation);
            return $id;
        });
    }

    public function updateAdmin(int $id,string $name,?string $description,int $cost,int $adminId,?string $ip,?string $correlation): void
    {
        $name=trim($name);
        if ($id<1 || $name==='' || \App\Support\Http::textLength($name)>150 || $cost<1 || $cost>1000000
            || ($description!==null && \App\Support\Http::textLength($description)>5000)) {
            throw new \InvalidArgumentException('Enter valid reward details.');
        }
        Database::transaction($this->pdo,function () use ($id,$name,$description,$cost,$adminId,$ip,$correlation): void {
            $before=$this->rewards->findByIdForUpdate($id);
            if ($before===null) { throw new \RuntimeException('Reward not found.'); }
            $this->rewards->updateDetails($id,$name,$description,$cost);
            $details=json_encode(['before'=>['name'=>$before['name'],'points_cost'=>(int)$before['points_cost']],
                'after'=>['name'=>$name,'points_cost'=>$cost]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $this->audit->record($adminId,'reward_updated','reward',$id,null,$details?:'Updated reward details.', $ip,$correlation);
        });
    }

    public function setActiveAdmin(int $id,bool $active,int $adminId,?string $ip,?string $correlation): void
    {
        Database::transaction($this->pdo,function () use ($id,$active,$adminId,$ip,$correlation): void {
            $before=$this->rewards->findByIdForUpdate($id);
            if ($before===null) { throw new \RuntimeException('Reward not found.'); }
            if ($this->rewards->setActive($id,$active)) {
                $this->audit->record($adminId,$active?'reward_activated':'reward_deactivated','reward',$id,null,
                    'is_active: '.(int)$before['is_active'].' → '.($active?'1':'0').'.', $ip,$correlation);
            }
        });
    }

    public function restockAdmin(int $id,int $amount,int $adminId,?string $ip,?string $correlation): void
    {
        if ($amount<1 || $amount>1000000000) { throw new \InvalidArgumentException('Enter a valid positive restock quantity.'); }
        Database::transaction($this->pdo,function () use ($id,$amount,$adminId,$ip,$correlation): void {
            $before=$this->rewards->findByIdForUpdate($id);
            if ($before===null) { throw new \RuntimeException('Reward not found.'); }
            if (!$this->rewards->addStock($id,$amount)) { throw new \RuntimeException('The stock quantity would exceed the database limit.'); }
            $after=$this->rewards->findById($id);
            $this->audit->record($adminId,'reward_restocked','reward',$id,null,
                'Stock increased from '.(int)$before['stock_quantity'].' to '.(int)($after['stock_quantity']??0).'.', $ip,$correlation);
        });
    }

    /** Participate in an existing RedemptionService transaction. */
    public function returnOneWithinTransaction(int $rewardId): void
    {
        $this->rewards->returnOne($rewardId);
    }

    public function activeCatalogue(): array
    {
        return $this->rewards->findActive();
    }

    public function find(int $rewardId): ?array
    {
        return $this->rewards->findById($rewardId);
    }

    /** Lock a reward so its price and active state cannot change during redemption. */
    public function findForRedemptionWithinTransaction(int $rewardId): ?array
    {
        return $this->rewards->findByIdForUpdate($rewardId);
    }

    /** Must be called inside the RedemptionService transaction. */
    public function reserveOneWithinTransaction(int $rewardId): bool
    {
        return $this->rewards->reserveOne($rewardId);
    }
}
