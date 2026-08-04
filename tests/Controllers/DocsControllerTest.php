<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * OpenAPI Documentation & Swagger UI Unit Tests
 */
declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\DocsController;
use Tests\TestCase;

class DocsControllerTest extends TestCase
{
    private DocsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new DocsController();
    }

    public function testGetJsonReturnsValidOpenApiJsonSchema(): void
    {
        $response = $this->controller->getJson();

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type'));
        
        $body = $response->body;
        $this->assertStringContainsString('NFC Inventory API', $body);
        $this->assertJson($body);
    }

    public function testGetUiReturnsInteractiveSwaggerHtmlViewer(): void
    {
        $response = $this->controller->getUi();

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
        
        $body = $response->body;
        $this->assertStringContainsString('swagger-ui', $body);
        $this->assertStringContainsString('./api-docs.json', $body);
    }
}
