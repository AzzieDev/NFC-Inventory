<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * PDO Database Connection Factory with Auto-Schema Initialization
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
    private static bool $schemaInitialized = false;

    /**
     * Retrieve active PDO database connection and guarantee tables exist
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
            
            // Automatically initialize database schema tables on first connection if they do not exist
            self::initSchema(self::$instance);

            return self::$instance;
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Automatically parse and execute schema.sql (CREATE TABLE IF NOT EXISTS) upon live database connection
     */
    private static function initSchema(PDO $pdo): void
    {
        if (self::$schemaInitialized) {
            return;
        }

        $sqlPath = (defined('APP_ROOT') ? APP_ROOT : realpath(__DIR__ . '/../..')) . '/sql/schema.sql';
        if (file_exists($sqlPath)) {
            $sql = (string) file_get_contents($sqlPath);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt !== '' && str_starts_with(strtoupper(ltrim($stmt)), 'CREATE TABLE')) {
                    $pdo->exec($stmt);
                }
            }
        }

        self::$schemaInitialized = true;
    }

    /**
     * Inject an isolated PDO connection (such as sqlite::memory:) for unit testing
     */
    public static function setTestPdo(?PDO $pdo): void
    {
        self::$testOverride = $pdo;
    }

    /**
     * Clear established connection and reset schema verification state
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$testOverride = null;
        self::$schemaInitialized = false;
    }
}
