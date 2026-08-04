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

    // 1. Automatically write exception details and stack traces to root error.log file
    $logPath = APP_ROOT . '/error.log';
    $logEntry = sprintf(
        "[%s] %s (%s: %d)\nStack Trace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @file_put_contents($logPath, $logEntry, FILE_APPEND);

    // 2. Determine if detailed diagnostic display should be exposed to the browser
    $isDebug = (defined('APP_DEBUG') && APP_DEBUG)
               || (defined('EMERGENCY_OVERRIDE') && EMERGENCY_OVERRIDE)
               || getenv('APP_DEBUG') === 'true';

    if ($isDebug) {
        echo "<div style='font-family: monospace; background: #1a1b26; color: #f7768e; padding: 20px; border-radius: 8px; border: 1px solid #ff0055;'>";
        echo "<h2 style='margin-top: 0; color: #ff0055;'>Application Exception Encountered</h2>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . " <em>(Line " . $e->getLine() . ")</em></p>";
        echo "<h4 style='color: #7aa2f7;'>Stack Trace:</h4>";
        echo "<pre style='background: #15161e; color: #a9b1d6; padding: 15px; border-radius: 6px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
        echo "<p style='color: #9ece6a; font-size: 12px; margin-bottom: 0;'>Logged to: <code>" . htmlspecialchars($logPath, ENT_QUOTES, 'UTF-8') . "</code></p>";
        echo "</div>";
    } else {
        echo "<div style='font-family: sans-serif; text-align: center; padding: 50px; color: #333;'>";
        echo "<h1>An unexpected application error occurred.</h1>";
        echo "<p>Please consult <strong>error.log</strong> in the server root for technical details, or set <code>define('APP_DEBUG', true);</code> in secrets.php.</p>";
        echo "</div>";
    }
}
