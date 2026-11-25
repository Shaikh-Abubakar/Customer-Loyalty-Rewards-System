<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    /** @param array{host:string,port:int,name:string,user:string,password:string} $settings */
    public static function connect(array $settings): PDO
    {
        $host = trim($settings['host'] ?? '');
        $port = (int) ($settings['port'] ?? 0);
        $name = trim($settings['name'] ?? '');
        $user = trim($settings['user'] ?? '');
        $password = (string) ($settings['password'] ?? '');

        if ($host === '' || $name === '' || $user === '' || $port < 1 || $port > 65535) {
            throw new RuntimeException('Database configuration is incomplete.');
        }
        if (!preg_match('/^[A-Za-z0-9_.:-]+$/', $host) || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Database connection settings contain an invalid host or database name.');
        }
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('The PHP PDO MySQL driver (pdo_mysql) is not enabled.');
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ]);

            // Keep connection timestamps aligned with the Stage 2 UTC DATETIME design.
            $pdo->exec("SET SESSION time_zone = '+00:00'");
            $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

            return $pdo;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database connection failed. Check that the database service is running and local settings are correct.',
                0,
                $exception
            );
        }
    }
}
