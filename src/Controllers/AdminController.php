<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Lightweight Admin Tag Binding Controller
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Models\Tag;

class AdminController
{
    private Tag $tagRepository;

    public function __construct(?Tag $tagRepository = null)
    {
        $this->tagRepository = $tagRepository ?? new Tag();
    }

    /**
     * Display simple tag assignment view (GET /admin/inventory?bind=UID)
     */
    public function showBindForm(array $params = [], string $basePath = ''): Response
    {
        $uid = isset($_GET['bind']) ? rawurldecode(trim((string) $_GET['bind'])) : '';
        $existing = $uid !== '' ? $this->tagRepository->findByUidOrSlug($uid) : null;

        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/admin_bind.php';
        
        ob_start();
        include $viewPath;
        return Response::html((string) ob_get_clean(), 200);
    }

    /**
     * Process tag binding submission (POST /admin/inventory/bind)
     */
    public function saveBind(array $params = [], string $basePath = ''): Response
    {
        $uid = trim((string) ($_POST['uid'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $targetUrl = trim((string) ($_POST['target_url'] ?? ''));
        $friendlyName = trim((string) ($_POST['friendly_name'] ?? $slug));

        if ($uid === '') {
            return Response::html('<h1>Error: Missing hardware Tag UID</h1><a href="javascript:history.back()">Back</a>', 400);
        }

        if ($slug === '') {
            $slug = 'item-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $uid));
        }

        // Save tag in database with assigned target destination
        $this->tagRepository->saveTag([
            'uid' => $uid,
            'slug' => $slug,
            'friendly_name' => $friendlyName,
            'target_url' => $targetUrl,
            'status' => 'active'
        ]);

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        
        // Immediately redirect to test the live routing of the newly assigned tag!
        return Response::redirect($prefix . '/' . rawurlencode($uid), 302);
    }
}
