<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\CustomerProfileRepository;
use PDO;

final class ProfileService
{
    private CustomerProfileRepository $profiles;

    public function __construct(PDO $pdo)
    {
        $this->profiles = new CustomerProfileRepository($pdo);
    }

    public function createForNewCustomer(int $userId, string $name, ?string $phone): void
    {
        $this->profiles->create($userId, $name, $phone);
    }

    public function findForCustomer(int $userId): ?array
    {
        return $this->profiles->findByUserId($userId);
    }
}
