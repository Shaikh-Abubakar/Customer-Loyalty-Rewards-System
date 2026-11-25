<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\CodesRepository;
use App\Repository\HmacKeyRegistryRepository;
use App\Support\CodeFormat;
use App\Support\Hmac;
use PDO;
use RuntimeException;

final class CodeVerifierService
{
    private CodesRepository $codes;
    private HmacKeyRegistryRepository $registry;

    public function __construct(private PDO $pdo)
    {
        $this->codes = new CodesRepository($pdo);
        $this->registry = new HmacKeyRegistryRepository($pdo);
    }

    public function findByPlaintext(string $input): ?array
    {
        $normalised = CodeFormat::normalize($input);
        if (!CodeFormat::isValid($normalised)) {
            throw new \InvalidArgumentException('The code format is invalid.');
        }

        $registryRows = $this->registry->findUsableKeys();
        $activeRows = array_values(array_filter($registryRows, static fn (array $row): bool => $row['status'] === 'active'));
        $activeKeyId = (int) app_config('hmac.active_key_id', 0);
        if (count($activeRows) !== 1 || (int) $activeRows[0]['key_id'] !== $activeKeyId || $activeKeyId < 1 || $activeKeyId > 65535) {
            throw new RuntimeException('HMAC key registry and application configuration do not agree.');
        }

        $configuredKeys = app_config('hmac.keys', []);
        if (!is_array($configuredKeys)) {
            throw new RuntimeException('HMAC key configuration is invalid.');
        }
        $usableKeys = [];
        foreach ($registryRows as $row) {
            if (($row['algorithm'] ?? '') !== 'HMAC-SHA256') {
                throw new RuntimeException('An unsupported HMAC algorithm is registered.');
            }
            $keyId = (int) $row['key_id'];
            if (!isset($configuredKeys[$keyId]) || !is_string($configuredKeys[$keyId])) {
                throw new RuntimeException('A required historical HMAC key is missing from configuration.');
            }
            $secret = Hmac::decodeSecret($configuredKeys[$keyId]);
            $expectedCheck = Hmac::keyCheck($secret);
            if (!is_string($row['key_check_value']) || !hash_equals($expectedCheck, $row['key_check_value'])) {
                throw new RuntimeException('An HMAC key does not match its registry check value.');
            }
            $usableKeys[] = ['key_id' => $keyId, 'secret' => $secret];
        }
        if ($usableKeys === []) {
            throw new RuntimeException('No usable HMAC keys are configured.');
        }

        foreach ($usableKeys as $key) {
            $hash = Hmac::codeHash($normalised, $key['secret']);
            $code = $this->codes->findByHmac($hash, $key['key_id']);
            if ($code !== null) {
                return $code;
            }
        }
        return null;
    }
}
