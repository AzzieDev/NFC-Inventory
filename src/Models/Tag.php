<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Tag Model / PDO Repository
 */
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;
use App\Utils\TagHelper;
use PDO;

class Tag
{
    private PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?? Connection::getPdo();
    }

    /**
     * Find a tag record by raw scanned hardware serial number OR custom friendly slug.
     * Automatically normalizes hardware serial inputs prior to performing strict prepared database lookup.
     *
     * @return array{uid: string, slug: ?string, friendly_name: ?string, post_id: ?int, target_url: ?string, status: string, created_at?: string, updated_at?: string}|null
     */
    public function findByUidOrSlug(string $identifier): ?array
    {
        $cleaned = trim($identifier);
        $normalizedUid = TagHelper::normalizeUid($cleaned);

        $stmt = $this->db->prepare('SELECT * FROM tags WHERE uid = :uid OR slug = :slug LIMIT 1');
        $stmt->execute([
            ':uid'  => $normalizedUid,
            ':slug' => strtolower($cleaned)
        ]);

        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        if ($row['post_id'] !== null) {
            $row['post_id'] = (int) $row['post_id'];
        }

        return $row;
    }

    /**
     * Legacy alias for direct UID lookups
     */
    public function findByUid(string $rawUid): ?array
    {
        return $this->findByUidOrSlug($rawUid);
    }

    /**
     * Create or update an NFC tag record in inventory with optional friendly slug and destination target_url
     */
    public function save(
        string $rawUid, 
        ?int $postId = null, 
        ?string $friendlyName = null, 
        string $status = 'available', 
        ?string $slug = null,
        ?string $targetUrl = null
    ): bool {
        $normalizedUid = TagHelper::normalizeUid($rawUid);
        if ($slug !== null) {
            $slug = strtolower(trim($slug));
        }

        $sql = "INSERT INTO tags (uid, slug, friendly_name, post_id, target_url, status) 
                VALUES (:uid, :slug, :name, :post_id, :target_url, :status)
                ON DUPLICATE KEY UPDATE 
                slug = VALUES(slug),
                friendly_name = VALUES(friendly_name), 
                post_id = VALUES(post_id), 
                target_url = VALUES(target_url),
                status = VALUES(status)";

        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = "INSERT OR REPLACE INTO tags (uid, slug, friendly_name, post_id, target_url, status) 
                    VALUES (:uid, :slug, :name, :post_id, :target_url, :status)";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':uid'        => $normalizedUid,
            ':slug'       => $slug,
            ':name'       => $friendlyName,
            ':post_id'    => $postId,
            ':target_url' => $targetUrl,
            ':status'     => $status,
        ]);
    }

    /**
     * Unlink a tag from its destination URL or record post and immediately return it to the available inventory pool
     */
    public function unlinkPost(string $rawUid): bool
    {
        $normalizedUid = TagHelper::normalizeUid($rawUid);
        $stmt = $this->db->prepare("UPDATE tags SET post_id = NULL, target_url = NULL, status = 'available' WHERE uid = :uid");
        return $stmt->execute([':uid' => $normalizedUid]);
    }
}
