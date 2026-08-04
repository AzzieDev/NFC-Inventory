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
    private string $basePath = '';

    public function __construct(string $rootPath = __DIR__ . '/..', bool $testMode = false)
    {
        Config::init($rootPath, $testMode);

        $this->router = new AltoRouter();
        
        // Ensure automatic base path extraction if deployed in a Laragon or sub-directory environment
        if (!$testMode && isset($_SERVER['SCRIPT_NAME'])) {
            $path = dirname($_SERVER['SCRIPT_NAME']);
            if ($path !== '/' && $path !== '\\' && $path !== '.') {
                $this->basePath = (string) $path;
                $this->router->setBasePath($this->basePath);
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
            $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/..') . '/src/Views/home.php';
            ob_start();
            include $viewPath;
            return Response::html((string) ob_get_clean(), 200);
        }, 'home');

        // --- Phase 4: Lightweight OAuth2 OpenID Connect (OIDC) Authentication ---
        $this->router->map('GET', '/login', [\App\Controllers\AuthController::class, 'login'], 'auth.login');
        $this->router->map('GET', '/login/callback', [\App\Controllers\AuthController::class, 'callback'], 'auth.callback');
        $this->router->map('GET', '/logout', [\App\Controllers\AuthController::class, 'logout'], 'auth.logout');

        // --- Phase 2: Admin Inventory Console & Tag Binding ---
        $this->router->map('GET', '/admin', [\App\Controllers\AdminController::class, 'index'], 'admin.index');
        $this->router->map('GET', '/admin/', [\App\Controllers\AdminController::class, 'index'], 'admin.index_slash');
        $this->router->map('GET', '/admin/inventory', [\App\Controllers\AdminController::class, 'showBindForm'], 'admin.bind');
        $this->router->map('GET', '/admin/inventory/bind', [\App\Controllers\AdminController::class, 'showBindForm'], 'admin.bind_get');
        $this->router->map('POST', '/admin/inventory/bind', [\App\Controllers\AdminController::class, 'saveBind'], 'admin.save_bind');
        $this->router->map('GET|POST', '/admin/inventory/delete', [\App\Controllers\AdminController::class, 'deleteTag'], 'admin.delete');

        // --- Phase 3: Automation & REST JSON Lookup API ---
        $this->router->map('GET', '/api/v1/lookup/[*:identifier]', [\App\Controllers\ApiController::class, 'lookup'], 'api.lookup');

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

            if ($controllerClass === NfcRouteController::class) {
                $response = $controller->$methodName((string) ($match['params']['tag_uid'] ?? ''), $this->basePath);
            } else {
                $response = $controller->$methodName($match['params'], $this->basePath);
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
     * Expose active base path string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Expose internal AltoRouter instance for unit testing inspection
     */
    public function getRouter(): AltoRouter
    {
        return $this->router;
    }
}
