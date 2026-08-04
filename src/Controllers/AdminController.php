<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Lightweight Admin Inventory Console & Tag Binding Controller
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
     * Display inventory overview table (GET /admin or /admin/inventory)
     */
    public function index(array $params = [], string $basePath = ''): Response
    {
        $tags = $this->tagRepository->getAll();
        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/admin_index.php';
        
        ob_start();
        include $viewPath;
        return Response::html((string) ob_get_clean(), 200);
    }

    /**
     * Display simple tag assignment view (GET /admin/inventory/bind?uid=UID)
     */
    public function showBindForm(array $params = [], string $basePath = ''): Response
    {
        // Accept 'bind_uid', 'uid', or legacy 'bind' query parameters
        $uid = '';
        if (isset($_GET['bind_uid'])) {
            $uid = rawurldecode(trim((string) $_GET['bind_uid']));
        } elseif (isset($_GET['uid'])) {
            $uid = rawurldecode(trim((string) $_GET['uid']));
        } elseif (isset($_GET['bind'])) {
            $uid = rawurldecode(trim((string) $_GET['bind']));
        }

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

        // Save tag in database repository using positional parameter sign-off
        $this->tagRepository->save(
            $uid,
            null,
            $friendlyName,
            'active',
            $slug,
            $targetUrl
        );

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        
        // Immediately redirect to test the live routing of the newly assigned tag!
        return Response::redirect($prefix . '/' . rawurlencode($uid), 302);
    }
}
