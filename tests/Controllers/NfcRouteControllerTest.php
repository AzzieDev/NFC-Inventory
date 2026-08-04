<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * NFC Routing Controller Unit Tests
 */
declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\NfcRouteController;
use App\Models\Tag;
use Tests\TestCase;

class NfcRouteControllerTest extends TestCase
{
    private NfcRouteController $controller;
    private Tag $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagRepository = new Tag($this->pdo);
        $this->controller = new NfcRouteController($this->tagRepository);
    }

    public function testScannedHardwareSerialRedirectsToFriendlySlugFirst(): void
    {
        // 1. Configure tag with hardware serial and friendly custom slug
        $this->tagRepository->save('04:6A:2B:92:44:00:80', null, 'Inventory Shelf #1', 'linked', 'shelf-01', 'https://example.com/item');

        // 2. Resolve via the hardware serial number directly
        $response = $this->controller->resolveTag('04:6A:2B:92:44:00:80');

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/shelf-01', $response->getHeader('Location'));
    }

    public function testVisitingFriendlySlugRedirectsToExternalTargetUrl(): void
    {
        $this->tagRepository->save('04:6A:2B:92:44:00:80', null, 'Inventory Shelf #1', 'linked', 'shelf-01', 'https://external-resource.com/doc');

        // Resolve via the friendly customizable slug
        $response = $this->controller->resolveTag('shelf-01');

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('https://external-resource.com/doc', $response->getHeader('Location'));
    }

    public function testVisitingFriendlySlugRedirectsToInternalRecordPost(): void
    {
        $postId = $this->insertPostFixture('Internal Item Doc', '## Specs');
        $this->tagRepository->save('item-uid-99', $postId, 'Item #99', 'linked', 'item-slug-99');

        $response = $this->controller->resolveTag('item-slug-99');

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/post/' . $postId, $response->getHeader('Location'));
    }

    public function testResolveTagServesUnassignedHtmlWhenTagIsUnlinked(): void
    {
        $this->tagRepository->save('unbound-01', null, 'Unassigned Tag', 'available');

        $response = $this->controller->resolveTag('unbound-01');

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('Tag Unassigned', $response->body);
        $this->assertStringContainsString('unbound-01', $response->body);
    }

    public function testResolveTagServesUnassignedHtmlWhenBrandNewSerialIsScanned(): void
    {
        $newSerial = '04:AA:FF:11:22:33:44';
        $response = $this->controller->resolveTag($newSerial);

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('Tag Unassigned', $response->body);
        $this->assertStringContainsString('04:AA:FF:11:22:33:44', $response->body);
    }
}
