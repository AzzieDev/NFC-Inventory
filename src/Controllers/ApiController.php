<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * REST & Automation API Controller (GET /api/v1/lookup/{identifier})
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Config;
use App\Http\Response;
use App\Models\Tag;
use OpenApi\Attributes as OA;

class ApiController
{
    private Tag $tagRepository;

    public function __construct(?Tag $tagRepository = null)
    {
        $this->tagRepository = $tagRepository ?? new Tag();
    }

    #[OA\Get(
        path: '/api/v1/lookup/{identifier}',
        summary: 'Primitive NFC Tag Inventory Lookup API — designed for automated task runners (Tasker, scripts) to check tag state and configuration',
        tags: ['Automation & REST API'],
        parameters: [
            new OA\Parameter(
                name: 'identifier',
                in: 'path',
                required: true,
                description: 'Physical NFC chip hardware serial number (e.g., 04:6A:F1:A2 or 046af1a2) or custom friendly slug',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'api_key',
                in: 'query',
                required: false,
                description: 'Authentication secret key matching API_KEY in secrets.php. Alternatively, pass via X-API-Key HTTP Header or Authorization Bearer token.',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag found in inventory database — returns full operational status, friendly name, slug, and destination link'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized — Missing or invalid API_KEY credential'
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found — Identifier does not correspond to any registered NFC tag or slug in inventory'
            )
        ]
    )]
    public function lookup(array $params = [], string $basePath = ''): Response
    {
        // 1. Verify API Key Authentication or Active Admin Session
        if (!$this->isAuthorized()) {
            return Response::json(json_encode([
                'status'  => 'error',
                'error'   => 'unauthorized',
                'message' => 'Invalid or missing API_KEY. Please provide X-API-Key header or ?api_key= parameter matching your secrets.php configuration.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 401);
        }

        $identifier = trim((string) ($params['identifier'] ?? ''));
        if ($identifier === '') {
            return Response::json(json_encode([
                'status'  => 'error',
                'error'   => 'bad_request',
                'message' => 'An identifier parameter is required.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 400);
        }

        // 2. Perform Database Query (supports normalized hardware serials & custom slugs)
        $tag = $this->tagRepository->findByUidOrSlug($identifier);

        if ($tag === null) {
            return Response::json(json_encode([
                'status'  => 'not_found',
                'found'   => false,
                'query'   => $identifier,
                'message' => 'Identifier not registered in NFC Inventory database.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 404);
        }

        // 3. Return Clean Machine-Readable JSON Payload
        return Response::json(json_encode([
            'status' => 'success',
            'found'  => true,
            'data'   => [
                'uid'           => $tag['uid'],
                'friendly_name' => $tag['friendly_name'] ?? null,
                'slug'          => $tag['slug'] ?? null,
                'target_url'    => $tag['target_url'] ?? null,
                'status'        => $tag['status'] ?? 'available',
                'updated_at'    => $tag['updated_at'] ?? null
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200);
    }

    /**
     * Authenticate request using configured API_KEY from secrets.php, HTTP headers, or admin session
     */
    private function isAuthorized(): bool
    {
        // Allow if emergency override or active admin browser session is enabled
        if (Config::isEmergencyOverride() || !empty($_SESSION['admin_logged_in'])) {
            return true;
        }

        $configuredKey = (string) Config::get('API_KEY', '');
        if ($configuredKey === '') {
            // If no key is defined in secrets.php, deny external API requests by default
            return false;
        }

        // Check query string ?api_key=...
        $providedKey = trim((string) ($_GET['api_key'] ?? ''));

        // Check HTTP header X-API-Key
        if ($providedKey === '') {
            $providedKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
        }

        // Check Authorization: Bearer <key>
        if ($providedKey === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.*)$/i', (string) $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $providedKey = trim($matches[1]);
            }
        }

        return hash_equals($configuredKey, $providedKey);
    }
}
