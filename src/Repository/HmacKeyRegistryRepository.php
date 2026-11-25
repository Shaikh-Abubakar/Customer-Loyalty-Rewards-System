<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class HmacKeyRegistryRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array{key_id:int,algorithm:string,status:string,key_check_value:string}> */
    public function findUsableKeys(): array
    {
        $statement = $this->pdo->query(
            "SELECT key_id, algorithm, status, key_check_value\n             FROM hmac_key_registry WHERE status IN ('active','retired') ORDER BY key_id"
        );
        return $statement->fetchAll();
    }
}
