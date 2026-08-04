<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * TagHelper Utility Unit Tests
 */
declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\TagHelper;
use PHPUnit\Framework\TestCase;

class TagHelperTest extends TestCase
{
    public function testNormalizeUidStripsColonsAndDashesFromHardwareSerial(): void
    {
        $rawWithColons = '04:6A:F1:2B:90:3C:80';
        $rawWithDashes = '04-6A-F1-2B-90-3C-80';
        $expected = '046af12b903c80';

        $this->assertSame($expected, TagHelper::normalizeUid($rawWithColons));
        $this->assertSame($expected, TagHelper::normalizeUid($rawWithDashes));
    }

    public function testNormalizeUidPreservesAndTrimsCustomSlugs(): void
    {
        $customSlug = '  item-01  ';
        $this->assertSame('item-01', TagHelper::normalizeUid($customSlug));
    }

    public function testIsHardwareSerialIdentifiesFactoryChipUids(): void
    {
        $fourByteSerial = '046a2f12';
        $sevenByteSerial = '046af12b903c80';
        $customSlug = 'custom-item-slug';

        $this->assertTrue(TagHelper::isHardwareSerial($fourByteSerial));
        $this->assertTrue(TagHelper::isHardwareSerial($sevenByteSerial));
        $this->assertFalse(TagHelper::isHardwareSerial($customSlug));
    }

    public function testFormatForDisplayConvertsNormalizedSerialBackToMacNotation(): void
    {
        $normalized = '046af12b903c80';
        $customSlug = 'item-01';

        $this->assertSame('04:6A:F1:2B:90:3C:80', TagHelper::formatForDisplay($normalized));
        $this->assertSame('item-01', TagHelper::formatForDisplay($customSlug));
    }
}
