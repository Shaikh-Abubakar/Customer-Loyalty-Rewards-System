<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class AccountStatusRepository
{
    public function __construct(private PDO $pdo) {}

    public function latestClosureRequest(int $userId): ?array
    {
        $stmt=$this->pdo->prepare("SELECT created_at,reason_text FROM account_status_events WHERE user_id=:id AND action='close_request' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['id'=>$userId]);
        $row=$stmt->fetch();
        return is_array($row)?$row:null;
    }

    public function add(int $userId,string $action,string $category,string $text,int $adminId): void
    {
        $stmt=$this->pdo->prepare('INSERT INTO account_status_events (user_id,action,reason_category,reason_text,acted_by_user_id,created_at) VALUES (:user_id,:action,:category,:text,:admin,UTC_TIMESTAMP())');
        $stmt->execute(['user_id'=>$userId,'action'=>$action,'category'=>$category,'text'=>$text,'admin'=>$adminId]);
    }
}
