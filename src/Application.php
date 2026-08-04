<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Lightweight Application Router & Bootstrap
 */
declare(strict_types=1);

namespace App;

use AltoRouter;
use App\Config\Config;
use App\Controllers\DocsController;
use App\Controllers\NfcRouteController;
use App\Http\Response;

class Application
{
    private AltoRouter $router;

    public function __construct(string $rootPath = __DIR__ . '/..', bool $testMode = false)
    {
        Config::init($rootPath, $testMode);

        $this->router = new AltoRouter();
        
        // Ensure automatic base path extraction if deployed in a Laragon sub-directory
        if (!$testMode && isset($_SERVER['SCRIPT_NAME'])) {
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            if ($basePath !== '/' && $basePath !== '\\' && $basePath !== '.') {
                $this->router->setBasePath((string) $basePath);
            }
        }

        $this->registerRoutes();
    }

    /**
     * Map static and dynamic application routes
     */
    private function registerRoutes(): void
    {
        // --- Static Documentation & API Schema Routes ---
        $this->router->map('GET', '/api-docs.json', [DocsController::class, 'getJson'], 'api.json');
        $this->router->map('GET', '/docs', [DocsController::class, 'getUi'], 'api.ui');

        // --- Catalog / Index Home ---
        $this->router->map('GET', '/', function () {
            return Response::html('<h1>NFC Inventory Tracker</h1><p>Visit <a href="./docs">/docs</a> for OpenAPI specifications.</p>');
        }, 'home');

        // --- NFC Routing Engine Fallback (Must be mapped last) ---
        $this->router->map('GET', '/[*:tag_uid]', [NfcRouteController::class, 'resolveTag'], 'nfc.resolve');
    }

    /**
     * Execute matched route and optionally emit HTTP response directly
     */
    public function run(?string $url = null, ?string $method = null, bool $emit = true): Response
    {
        $match = $this->router->match($url, $method);

        if ($match && is_callable($match['target'])) {
            // Handle Closure route targets
            $response = call_user_func($match['target']);
        } elseif ($match && is_array($match['target'])) {
            // Handle Controller class targets
            [$controllerClass, $methodName] = $match['target'];
            $controller = new $controllerClass();

            $basePath = $this->router->getBasePath();
            if ($controllerClass === NfcRouteController::class) {
                $response = $controller->$methodName((string) ($match['params']['tag_uid'] ?? ''), $basePath);
            } else {
                $response = $controller->$methodName($match['params'], $basePath);
            }
        } else {
            // Unmatched Route / 404 Not Found
            $response = Response::html('<h1>404 Not Found</h1><p>The requested resource does not exist.</p>', 404);
        }

        if ($emit) {
            $response->emit();
        }

        return $response;
    }

    /**
     * Expose internal AltoRouter instance for unit testing inspection
     */
    public function getRouter(): AltoRouter
    {
        return $this->router;
    }
}
