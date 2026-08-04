<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Config Loader
 */
declare(strict_types=1);

namespace App\Config;

class Config
{
    private static bool $loaded = false;

    /**
     * Initialize configuration by loading secrets.php or falling back to secrets.sample.php
     */
    public static function init(string $rootPath, bool $forceSample = false): void
    {
        if (self::$loaded && !$forceSample) {
            return;
        }

        if (!defined('APP_ROOT')) {
            define('APP_ROOT', $rootPath);
        }

        $secretsPath = $rootPath . '/secrets.php';
        $samplePath = $rootPath . '/secrets.sample.php';

        if (!$forceSample && file_exists($secretsPath)) {
            require_once $secretsPath;
        } elseif (file_exists($samplePath)) {
            require_once $samplePath;
        } else {
            throw new \RuntimeException('Neither secrets.php nor secrets.sample.php could be located in ' . $rootPath);
        }

        self::$loaded = true;
    }

    /**
     * Retrieve a configuration constant by name
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (defined($key)) {
            return constant($key);
        }
        return $default;
    }

    /**
     * Check if emergency admin override is enabled
     */
    public static function isEmergencyOverride(): bool
    {
        return (bool) self::get('EMERGENCY_OVERRIDE', false);
    }

    /**
     * Reset loaded state (primarily useful for unit tests)
     */
    public static function reset(): void
    {
        self::$loaded = false;
    }
}
