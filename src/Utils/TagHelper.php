<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * NFC Tag UID & Hardware Serial Number Normalization Utility
 */
declare(strict_types=1);

namespace App\Utils;

class TagHelper
{
    /**
     * Normalize a raw scanned tag UID or NTAG hardware serial number.
     * Removes MAC-style formatting colons/dashes from hardware serials while preserving hyphens in customizable slugs.
     *
     * Example: "04:6A:F1:2B:90:3C:80" -> "046af12b903c80"
     * Example: "04-6A-F1-2B-90-3C-80" -> "046af12b903c80"
     * Example: "item-01" -> "item-01"
     */
    public static function normalizeUid(string $rawUid): string
    {
        $cleaned = strtolower(trim($rawUid));
        
        // Always strip colons and whitespace from input strings
        $strippedColons = preg_replace('/[:\s]+/', '', $cleaned) ?? $cleaned;

        // Check if removing hyphens yields a standard NTAG hardware hex serial number
        $withoutDashes = str_replace('-', '', $strippedColons);
        if (self::isHardwareSerial($withoutDashes)) {
            return $withoutDashes;
        }

        // Return customizable friendly slug preserving user hyphens
        return $strippedColons;
    }

    /**
     * Determine if a normalized string resembles a standard factory NTAG hex serial number
     * (Typically 4-byte / 8 hex characters or 7-byte / 14 hex characters).
     */
    public static function isHardwareSerial(string $normalizedUid): bool
    {
        $len = strlen($normalizedUid);
        if (!in_array($len, [8, 14], true)) {
            return false;
        }

        return ctype_xdigit($normalizedUid);
    }

    /**
     * Format a normalized hardware serial number back into standard uppercase colon-delimited MAC notation for human display in diagnostic admin UI.
     * Example: "046af12b903c80" -> "04:6A:F1:2B:90:3C:80"
     */
    public static function formatForDisplay(string $normalizedUid): string
    {
        if (!self::isHardwareSerial($normalizedUid)) {
            return $normalizedUid; // Return custom friendly slug unmodified
        }

        $upper = strtoupper($normalizedUid);
        $pairs = str_split($upper, 2);
        return implode(':', $pairs);
    }
}
