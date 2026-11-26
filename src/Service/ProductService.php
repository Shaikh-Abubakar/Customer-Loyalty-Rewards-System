<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\AuditLogRepository;
use App\Repository\ProductRepository;
use App\Support\Database;
use App\Support\Http;
use PDO;
use PDOException;
use RuntimeException;

final class ProductService
{
    private ProductRepository $products;
    private AuditLogRepository $audit;

    public function __construct(private PDO $pdo)
    {
        $this->products=new ProductRepository($pdo);
        $this->audit=new AuditLogRepository($pdo);
    }

    public function save(?int $id,string $sku,string $name,string $description,int $points,int $adminId,?string $ip,?string $correlation): int
    {
        $sku=strtoupper(trim($sku)); $name=trim($name); $description=trim($description);
        if ($sku==='' || strlen($sku)>40 || preg_match('/^[A-Z0-9][A-Z0-9._-]*$/D',$sku)!==1
            || $name==='' || Http::textLength($name)>150 || Http::textLength($description)>10000
            || $points<1 || $points>10000) {
            throw new \InvalidArgumentException('Enter a valid SKU, product name and points value (1–10,000).');
        }
        try {
            return Database::transaction($this->pdo,function () use ($id,$sku,$name,$description,$points,$adminId,$ip,$correlation): int {
                if ($id===null) {
                    $newId=$this->products->create($sku,$name,$description!==''?$description:null,$points,$adminId);
                    $this->audit->record($adminId,'product_created','product',$newId,null,'Created product '.$sku.'.',$ip,$correlation);
                    return $newId;
                }
                $before=$id>0?$this->products->find($id):null;
                if ($before===null) { throw new RuntimeException('Product not found.'); }
                $this->products->update($id,$sku,$name,$description!==''?$description:null,$points);
                $details=json_encode(['before'=>['sku'=>$before['sku'],'name'=>$before['name'],'default_points'=>(int)$before['default_points'],'is_active'=>(int)$before['is_active']],
                    'after'=>['sku'=>$sku,'name'=>$name,'default_points'=>$points,'is_active'=>(int)$before['is_active']]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                $this->audit->record($adminId,'product_updated','product',$id,null,$details?:'Product updated.',$ip,$correlation);
                return $id;
            });
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1]??0)===1062) { throw new \InvalidArgumentException('That SKU is already in use.'); }
            throw $e;
        }
    }

    public function setActive(int $id,bool $active,int $adminId,?string $ip,?string $correlation): void
    {
        Database::transaction($this->pdo,function () use ($id,$active,$adminId,$ip,$correlation): void {
            $before=$this->products->find($id);
            if ($before===null) { throw new RuntimeException('Product not found.'); }
            if ($this->products->setActive($id,$active)) {
                $this->audit->record($adminId,$active?'product_activated':'product_deactivated','product',$id,null,
                    'is_active: '.(int)$before['is_active'].' → '.($active?'1':'0').'.',$ip,$correlation);
            }
        });
    }
}
