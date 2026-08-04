<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * OpenAPI Documentation & Swagger UI Controller
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use OpenApi\Attributes as OA;
use OpenApi\Generator;

class DocsController
{
    #[OA\Get(
        path: '/api-docs.json',
        summary: 'Generate OpenAPI 3.0 specification in standard JSON format',
        tags: ['System & Documentation'],
        responses: [
            new OA\Response(response: 200, description: 'Successful OpenAPI schema generation')
        ]
    )]
    public function getJson(): Response
    {
        $srcDir = defined('APP_ROOT') ? APP_ROOT . '/src' : __DIR__ . '/..';
        $openapi = Generator::scan([$srcDir]);
        
        return Response::json($openapi->toJson());
    }

    #[OA\Get(
        path: '/docs',
        summary: 'Interactive Swagger UI Documentation Viewer',
        tags: ['System & Documentation'],
        responses: [
            new OA\Response(response: 200, description: 'HTML interactive Swagger viewer')
        ]
    )]
    public function getUi(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NFC Inventory - API & Routing Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    <style>
        body { margin: 0; padding: 0; background: #0f1117; color: #e2e8f0; font-family: sans-serif; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui { filter: invert(88%) hue-rotate(180deg); }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: './api-docs.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout"
            });
        };
    </script>
</body>
</html>
HTML;
        return Response::html($html);
    }
}
