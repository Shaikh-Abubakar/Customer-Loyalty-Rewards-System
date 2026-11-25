<?php
declare(strict_types=1);

use App\Support\Database;
use App\Support\Hmac;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $boot = require dirname(__DIR__) . '/bootstrap/cli.php';
    $config = $boot['config'];
    $keyId = (int) ($config['hmac']['active_key_id'] ?? 0);
    $encodedKey = $config['hmac']['keys'][$keyId] ?? null;
    if ($keyId < 1 || $keyId > 65535 || !is_string($encodedKey)) {
        throw new RuntimeException('Set HMAC_ACTIVE_KEY_ID and its matching HMAC_KEY_<id> in .env first.');
    }

    $secret = Hmac::decodeSecret($encodedKey);
    $pdo = Database::connect($config['database']);

    $registryCount = (int) $pdo->query('SELECT COUNT(*) FROM hmac_key_registry')->fetchColumn();
    if ($registryCount !== 0) {
        throw new RuntimeException('The HMAC key registry is not empty. This one-time command only provisions a new installation; use the approved key-management procedure for later changes.');
    }

    $insert = $pdo->prepare(
        "INSERT INTO hmac_key_registry\n         (key_id, label, algorithm, status, key_check_value, created_at, activated_at, notes)\n         VALUES (:key_id, :label, 'HMAC-SHA256', 'active', :check_value, UTC_TIMESTAMP(), UTC_TIMESTAMP(), :notes)"
    );
    $insert->bindValue(':key_id', $keyId, PDO::PARAM_INT);
    $insert->bindValue(':label', 'Initial claim key ' . $keyId, PDO::PARAM_STR);
    $insert->bindValue(':check_value', Hmac::keyCheck($secret), PDO::PARAM_LOB);
    $insert->bindValue(':notes', 'Registered by the one-time Stage 5 initial key setup command.', PDO::PARAM_STR);
    $insert->execute();

    fwrite(STDOUT, "Registered active HMAC key ID {$keyId}. The secret was not stored in the database or printed.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'HMAC key setup failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
