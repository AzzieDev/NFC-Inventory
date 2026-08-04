<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Main Front Controller
 */
declare(strict_types=1);

// Define global application execution root (security shield for included config/secrets)
define('APP_ROOT', __DIR__);

// Verify composer dependencies are present
if (!file_exists(APP_ROOT . '/vendor/autoload.php')) {
    http_response_code(500);
    die("Composer autoloader not found. Please run 'composer install' in the repository root.");
}

require_once APP_ROOT . '/vendor/autoload.php';

try {
    // Instantiate lightweight zero-bloat Application router and process incoming HTTP request
    $app = new \App\Application(APP_ROOT);
    $app->run();
} catch (\Throwable $e) {
    http_response_code(500);
    echo "An unexpected application error occurred.";
    if (getenv('APP_DEBUG') === 'true') {
        echo "<pre>" . htmlspecialchars((string) $e) . "</pre>";
    }
}
