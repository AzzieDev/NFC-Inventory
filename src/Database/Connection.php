<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * PDO Database Connection Factory
 */
declare(strict_types=1);

namespace App\Database;

use App\Config\Config;
use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private static ?PDO $instance = null;
    private static ?PDO $testOverride = null;

    /**
     * Retrieve active PDO database connection
     */
    public static function getPdo(): PDO
    {
        if (self::$testOverride !== null) {
            return self::$testOverride;
        }

        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = Config::get('DB_HOST', 'localhost');
        $db   = Config::get('DB_NAME', 'nfc_inventory');
        $user = Config::get('DB_USER', 'root');
        $pass = Config::get('DB_PASS', '');
        $charset = Config::get('DB_CHARSET', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $db, $charset);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$instance = new PDO($dsn, (string) $user, (string) $pass, $options);
            return self::$instance;
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Inject an isolated PDO connection (such as sqlite::memory:) for unit testing
     */
    public static function setTestPdo(?PDO $pdo): void
    {
        self::$testOverride = $pdo;
    }

    /**
     * Clear established connection
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$testOverride = null;
    }
}
