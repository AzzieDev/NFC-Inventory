<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * PHPUnit Master Test Case Helper
 */
declare(strict_types=1);

namespace Tests;

use App\Config\Config;
use App\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $rootPath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', $rootPath);
        }

        Config::init(APP_ROOT, true);

        // Instantiate isolated in-memory SQLite database for high-speed unit testing
        $this->pdo = new PDO('sqlite::memory:', '', '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->setupMemoryTables();
        Connection::setTestPdo($this->pdo);
    }

    protected function tearDown(): void
    {
        Connection::reset();
        Config::reset();
        parent::tearDown();
    }

    /**
     * Build SQLite-compatible test tables representing production MySQL schema
     */
    private function setupMemoryTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                markdown_content TEXT NOT NULL,
                rating INTEGER DEFAULT NULL,
                comments TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS tags (
                uid TEXT PRIMARY KEY,
                slug TEXT UNIQUE DEFAULT NULL,
                friendly_name TEXT DEFAULT NULL,
                post_id INTEGER DEFAULT NULL,
                target_url TEXT DEFAULT NULL,
                status TEXT NOT NULL DEFAULT 'available',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE SET NULL
            );
        ");
    }

    /**
     * Insert mock record fixture and return generated ID
     */
    protected function insertPostFixture(string $title = 'Sample Inventory Record', string $markdown = '## Record Notes'): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO posts (title, markdown_content) VALUES (:t, :m)');
        $stmt->execute([':t' => $title, ':m' => $markdown]);
        return (int) $this->pdo->lastInsertId();
    }
}
