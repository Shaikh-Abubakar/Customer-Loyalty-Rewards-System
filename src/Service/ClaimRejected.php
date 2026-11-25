<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class ClaimRejected extends RuntimeException
{
    public string $outcome;

    public function __construct(string $outcome)
    {
        $this->outcome = $outcome;
        parent::__construct('The claim could not be completed.');
    }
}
