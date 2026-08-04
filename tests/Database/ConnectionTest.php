<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * PDO Database Connection Unit Tests
 */
declare(strict_types=1);

namespace Tests\Database;

use App\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testGetPdoReturnsActivePdoInstanceWithExceptionMode(): void
    {
        $testPdo = new PDO('sqlite::memory:', '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        Connection::setTestPdo($testPdo);
        $retrieved = Connection::getPdo();

        $this->assertInstanceOf(PDO::class, $retrieved);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $retrieved->getAttribute(PDO::ATTR_ERRMODE));

        Connection::reset();
    }
}
