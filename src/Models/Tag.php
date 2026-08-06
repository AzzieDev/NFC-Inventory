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

        $existing = $this->findByUidOrSlug($normalizedUid);
        $action = ($existing === null) ? 'assigned' : 'updated';
        $oldUrl = $existing['target_url'] ?? null;
        $oldName = $existing['friendly_name'] ?? null;

        $sql = "INSERT INTO tags (uid, slug, friendly_name, post_id, target_url, status) 
                VALUES (:uid, :slug, :name, :post_id, :target_url, :status)
                ON DUPLICATE KEY UPDATE 
                slug = VALUES(slug),
                friendly_name = VALUES(friendly_name), 
                post_id = VALUES(post_id), 
                target_url = VALUES(target_url),
                status = VALUES(status)";

        if ($this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = "INSERT OR REPLACE INTO tags (uid, slug, friendly_name, post_id, target_url, status) 
                    VALUES (:uid, :slug, :name, :post_id, :target_url, :status)";
        }

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':uid'        => $normalizedUid,
            ':slug'       => $slug,
            ':name'       => $friendlyName,
            ':post_id'    => $postId,
            ':target_url' => $targetUrl,
            ':status'     => $status,
        ]);

        if ($result && $action !== 'reverted') {
            $this->recordActivity($normalizedUid, $action, $oldUrl, $targetUrl, $oldName, $friendlyName);
        }

        return $result;
    }

    /**
     * Unlink a tag from its destination URL or record post and immediately return it to the available inventory pool
     */
    public function unlinkPost(string $rawUid): bool
    {
        $normalizedUid = TagHelper::normalizeUid($rawUid);
        $existing = $this->findByUidOrSlug($normalizedUid);
        
        $stmt = $this->db->prepare("UPDATE tags SET post_id = NULL, target_url = NULL, status = 'available' WHERE uid = :uid");
        $result = $stmt->execute([':uid' => $normalizedUid]);

        if ($result && $existing !== null && ($existing['target_url'] !== null || $existing['post_id'] !== null)) {
            $this->recordActivity($existing['uid'], 'unassigned', $existing['target_url'], null, $existing['friendly_name'], $existing['friendly_name']);
        }

        return $result;
    }

    /**
     * Retrieve all inventory tag records ordered by latest updates
     *
     * @return array<int, array>
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM tags ORDER BY updated_at DESC, created_at DESC');
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Permanently remove a tag record from the inventory database
     */
    public function delete(string $rawUid): bool
    {
        $normalizedUid = TagHelper::normalizeUid($rawUid);
        $existing = $this->findByUidOrSlug($normalizedUid);

        $stmt = $this->db->prepare('DELETE FROM tags WHERE uid = :uid');
        $result = $stmt->execute([':uid' => $normalizedUid]);

        if ($result && $existing !== null) {
            $this->recordActivity($existing['uid'], 'deleted', $existing['target_url'], null, $existing['friendly_name'], null);
        }

        return $result;
    }

    /**
     * Record an audit log entry for historical activity tracking and one-click state reversibility
     */
    public function recordActivity(string $uid, string $action, ?string $oldUrl, ?string $newUrl, ?string $oldName, ?string $newName): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO tag_activity_logs (tag_uid, action_type, old_target_url, new_target_url, old_friendly_name, new_friendly_name) VALUES (:uid, :action, :old_url, :new_url, :old_name, :new_name)");
            $stmt->execute([
                ':uid'      => $uid,
                ':action'   => $action,
                ':old_url'  => $oldUrl,
                ':new_url'  => $newUrl,
                ':old_name' => $oldName,
                ':new_name' => $newName
            ]);
        } catch (\PDOException $e) {
            // Suppress log insert errors during schema transitions
        }
    }

    /**
     * Retrieve recent chronological tag activity logs
     *
     * @return array<int, array>
     */
    public function getHistory(?string $uid = null, int $limit = 100): array
    {
        try {
            if ($uid !== null) {
                $stmt = $this->db->prepare('SELECT * FROM tag_activity_logs WHERE tag_uid = :uid ORDER BY created_at DESC, id DESC LIMIT :limit');
                $stmt->bindValue(':uid', TagHelper::normalizeUid($uid), \PDO::PARAM_STR);
                $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll();
            }
            
            $stmt = $this->db->query("SELECT * FROM tag_activity_logs ORDER BY created_at DESC, id DESC LIMIT {$limit}");
            return $stmt ? $stmt->fetchAll() : [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Revert a tag to its prior state recorded in an audit log entry
     */
    public function revertFromLog(int $logId): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM tag_activity_logs WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $logId]);
            $log = $stmt->fetch();

            if ($log === false) {
                return false;
            }

            $uid = (string) $log['tag_uid'];
            $targetToRestore = $log['old_target_url'];
            $nameToRestore   = $log['old_friendly_name'];

            $existing = $this->findByUidOrSlug($uid);
            $currentUrl  = $existing['target_url'] ?? null;
            $currentName = $existing['friendly_name'] ?? null;

            // Save without re-triggering standard updated activity log
            $stmtSave = $this->db->prepare("INSERT INTO tags (uid, friendly_name, target_url, status) VALUES (:uid, :name, :url, 'assigned') ON DUPLICATE KEY UPDATE friendly_name = VALUES(friendly_name), target_url = VALUES(target_url), status = 'assigned'");
            $res = $stmtSave->execute([
                ':uid'  => $uid,
                ':name' => $nameToRestore,
                ':url'  => $targetToRestore
            ]);

            if ($res) {
                $this->recordActivity($uid, 'reverted', $currentUrl, $targetToRestore, $currentName, $nameToRestore);
            }

            return $res;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
