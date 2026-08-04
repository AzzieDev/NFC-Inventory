<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Tag Model / PDO Repository Unit Tests
 */
declare(strict_types=1);

namespace Tests\Models;

use App\Models\Tag;
use Tests\TestCase;

class TagModelTest extends TestCase
{
    private Tag $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagRepository = new Tag($this->pdo);
    }

    public function testSaveAndFindTagNormalizesHardwareSerialNumbers(): void
    {
        $rawAndroidScan = '04:6A:F1:2B:90:3C:80';
        
        $saved = $this->tagRepository->save($rawAndroidScan, null, 'Inventory Box #1', 'available');
        $this->assertTrue($saved);

        // Query using clean lowercase hex string or mixed syntax
        $retrieved = $this->tagRepository->findByUid('046af12b903c80');
        $this->assertNotNull($retrieved);
        $this->assertSame('046af12b903c80', $retrieved['uid']);
        $this->assertSame('Inventory Box #1', $retrieved['friendly_name']);
        $this->assertNull($retrieved['post_id']);
    }

    public function testTagCanBeLookedUpByCustomizableSlug(): void
    {
        $this->tagRepository->save('04:11:22:33:44:55:66', null, 'Shelf Tag', 'linked', 'custom-shelf-slug', 'https://external-resource.com');

        $tagBySerial = $this->tagRepository->findByUidOrSlug('04:11:22:33:44:55:66');
        $tagBySlug   = $this->tagRepository->findByUidOrSlug('custom-shelf-slug');

        $this->assertNotNull($tagBySerial);
        $this->assertNotNull($tagBySlug);
        $this->assertSame($tagBySerial, $tagBySlug);
        $this->assertSame('custom-shelf-slug', $tagBySlug['slug']);
        $this->assertSame('https://external-resource.com', $tagBySlug['target_url']);
    }

    public function testTagCanLinkToInternalRecordPostId(): void
    {
        $postId = $this->insertPostFixture('Sample Record #1', '## Record Content');
        
        $this->tagRepository->save('item-01', $postId, 'Tag #1', 'linked', 'item-slug-01');
        
        $tag = $this->tagRepository->findByUid('item-slug-01');
        $this->assertSame($postId, $tag['post_id']);
        $this->assertSame('linked', $tag['status']);
    }

    public function testUnlinkPostReturnsTagToAvailableInventoryPool(): void
    {
        $postId = $this->insertPostFixture('Archived Item', '## Item Info');
        $this->tagRepository->save('item-02', $postId, 'Tag #2', 'linked', null, 'https://example.org');

        $unlinked = $this->tagRepository->unlinkPost('item-02');
        $this->assertTrue($unlinked);

        $tag = $this->tagRepository->findByUid('item-02');
        $this->assertNull($tag['post_id']);
        $this->assertNull($tag['target_url']);
        $this->assertSame('available', $tag['status']);
    }
}
