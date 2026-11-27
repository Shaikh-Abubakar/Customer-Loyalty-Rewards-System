<?php
declare(strict_types=1);

use App\Support\Database;
use App\Support\Logger;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $boot = require dirname(__DIR__) . '/bootstrap/cli.php';
    $settings = $boot['config']['database_maintenance'] ?? [];
    if (trim((string) ($settings['user'] ?? '')) === '' || (string) ($settings['password'] ?? '') === '') {
        throw new RuntimeException('Set the separate DB_MAINTENANCE_USER and DB_MAINTENANCE_PASSWORD in .env first.');
    }
    $pdo = Database::connect($settings);
    $deleted = Database::transaction($pdo, static function () use ($pdo): array {
        $counts = [];
        foreach (['login_attempts', 'claim_attempts', 'registration_attempts'] as $table) {
            // Table names are a fixed internal allowlist, never user input.
            $statement = $pdo->prepare("DELETE FROM {$table} WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 90 DAY)");
            $statement->execute();
            $counts[$table] = $statement->rowCount();
        }
        return $counts;
    });

    $logDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    $logCutoff = gmdate('Y-m-d', time() - (30 * 86400));
    $deletedLogs = 0;
    foreach (glob($logDirectory . DIRECTORY_SEPARATOR . 'application-????-??-??.log') ?: [] as $file) {
        if (is_link($file) || !is_file($file)) {
            continue;
        }
        if (preg_match('/application-(\d{4}-\d{2}-\d{2})\.log$/D', basename($file), $m) === 1
            && $m[1] < $logCutoff && @unlink($file)) {
            $deletedLogs++;
        }
    }

    Logger::configure($logDirectory);
    Logger::notice('Retention maintenance completed; deleted rows=' . json_encode($deleted)
        . '; deleted_log_files=' . $deletedLogs, (string) ($boot['correlation_id'] ?? 'cli'));
    fwrite(STDOUT, "Retention maintenance completed.\n");
    foreach ($deleted as $table => $count) {
        fwrite(STDOUT, $table . ': ' . $count . " rows removed\n");
    }
    fwrite(STDOUT, 'Technical log files removed: ' . $deletedLogs . "\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Retention maintenance failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
