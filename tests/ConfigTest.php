<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Configuration & Secrets Unit Tests
 */
declare(strict_types=1);

namespace Tests;

use App\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $rootPath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
        Config::init($rootPath, true);
    }

    protected function tearDown(): void
    {
        Config::reset();
        parent::tearDown();
    }

    public function testConfigLoadsDatabaseConstantsFromSample(): void
    {
        $this->assertNotEmpty(Config::get('DB_HOST'));
        $this->assertNotEmpty(Config::get('DB_NAME'));
        $this->assertNotNull(Config::get('DB_USER'));
    }

    public function testEmergencyOverrideDefaultStateIsFalse(): void
    {
        $this->assertFalse(Config::isEmergencyOverride());
    }

    public function testConfigGetReturnsDefaultWhenConstantUndefined(): void
    {
        $value = Config::get('NON_EXISTENT_KEY', 'default_val');
        $this->assertSame('default_val', $value);
    }

    public function testTimezoneIsConfiguredToEasternTime(): void
    {
        $this->assertSame('America/New_York', date_default_timezone_get());
    }
}
