<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Lightweight Admin Inventory Console & Tag Binding Controller with OAuth2 Authentication Guard
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Config;
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
     * Guard verifying valid administrator authentication session or EMERGENCY_OVERRIDE
     */
    private function requireAuth(string $basePath): ?Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_lifetime' => 2592000, 'gc_maxlifetime' => 2592000]);
        }

        if (!empty($_SESSION['admin_logged_in']) || Config::isEmergencyOverride()) {
            return null; // Authorised or emergency override active
        }

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        return Response::redirect($prefix . '/login', 302);
    }

    /**
     * Display inventory overview table (GET /admin or /admin/inventory)
     */
    public function index(array $params = [], string $basePath = ''): Response
    {
        if (($redirect = $this->requireAuth($basePath)) !== null) {
            return $redirect;
        }

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
        if (($redirect = $this->requireAuth($basePath)) !== null) {
            return $redirect;
        }

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
        if ($existing !== null) {
            $uid = (string) $existing['uid'];
        }

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
        if (($redirect = $this->requireAuth($basePath)) !== null) {
            return $redirect;
        }

        $uid = trim((string) ($_POST['uid'] ?? ''));
        $slug = trim(trim((string) ($_POST['slug'] ?? '')), '/');
        $targetUrl = trim((string) ($_POST['target_url'] ?? ''));
        $friendlyName = trim((string) ($_POST['friendly_name'] ?? ''));

        if ($uid === '') {
            return Response::html('<h1>Error: Missing hardware Tag UID</h1><a href="javascript:history.back()">Back</a>', 400);
        }

        if ($slug === '') {
            $slug = null;
        }
        if ($friendlyName === '') {
            $friendlyName = null;
        }
        if ($targetUrl === '') {
            $targetUrl = null;
        }

        // Save tag in database repository using positional parameter sign-off
        try {
            $this->tagRepository->save(
                $uid,
                null,
                $friendlyName,
                'active',
                $slug,
                $targetUrl
            );
        } catch (\Exception $e) {
            return Response::html('
                <!DOCTYPE html>
                <html lang="en" class="dark">
                <head>
                    <title>Submission Error</title>
                    <script src="https://cdn.tailwindcss.com"></script>
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                    <style>body { background-color: #0b0d14; }</style>
                </head>
                <body class="text-slate-100 p-8 flex items-center justify-center min-h-screen font-sans">
                    <main class="max-w-md w-full bg-slate-900 border border-amber-500/30 rounded-2xl p-8 space-y-4 shadow-xl text-center">
                        <div class="text-amber-400 text-5xl mb-2"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <h1 class="text-2xl font-bold text-white tracking-wide">Duplicate Slug</h1>
                        <p class="text-slate-300 text-sm leading-relaxed">' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</p>
                        <div class="pt-4">
                            <a href="javascript:history.back()" class="inline-flex items-center gap-2 px-6 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white rounded-xl font-semibold transition shadow-lg"><i class="fa-solid fa-arrow-left"></i> Go Back and Change Slug</a>
                        </div>
                    </main>
                </body>
                </html>
            ', 400);
        }

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        
        // Immediately redirect to test the live routing of the newly assigned tag!
        return Response::redirect($prefix . '/' . rawurlencode($uid), 302);
    }

    /**
     * Process tag record deletion (GET or POST /admin/inventory/delete?uid=UID)
     */
    public function deleteTag(array $params = [], string $basePath = ''): Response
    {
        if (($redirect = $this->requireAuth($basePath)) !== null) {
            return $redirect;
        }

        $uid = trim((string) ($_GET['uid'] ?? ($_POST['uid'] ?? '')));
        if ($uid !== '') {
            $this->tagRepository->delete($uid);
        }

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        return Response::redirect($prefix . '/admin', 302);
    }

    /**
     * Display mobile-first interactive iFrame Bookmark Browser (GET /browse)
     */
    public function browser(array $params = [], string $basePath = ''): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_lifetime' => 2592000, 'gc_maxlifetime' => 2592000]);
        }
        $isAdmin = !empty($_SESSION['admin_logged_in']) || Config::isEmergencyOverride();

        $requestedUrl = trim((string) ($_GET['p'] ?? ''));
        if ($requestedUrl === '' || $requestedUrl === '/' || $requestedUrl === '/markdown-blog' || $requestedUrl === './markdown-blog' || $requestedUrl === '/blog' || $requestedUrl === '/content') {
            $contentController = new \App\Controllers\ContentController();
            return $contentController->index($params, $basePath);
        }

        // Delegate internal data service routes directly to ContentController for native iframe-less rendering
        if (str_starts_with($requestedUrl, '/content/')) {
            $contentController = new \App\Controllers\ContentController();
            $slug = substr($requestedUrl, strlen('/content/'));
            return $contentController->view(['slug' => $slug], $basePath);
        }

        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/admin_browser.php';
        ob_start();
        include $viewPath;
        return Response::html((string) ob_get_clean(), 200);
    }

    /**
     * Process instantaneous zero-confirmation JSON POST tag binding from mobile Web NFC sensor
     */
    public function fastBind(array $params = [], string $basePath = ''): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_lifetime' => 2592000, 'gc_maxlifetime' => 2592000]);
        }

        if (empty($_SESSION['admin_logged_in']) && !Config::isEmergencyOverride()) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Unauthorized admin session.']), 401);
        }

        $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $uid = trim((string) ($input['uid'] ?? ''));
        $targetUrl = trim((string) ($input['target_url'] ?? ''));
        $friendlyName = trim((string) ($input['friendly_name'] ?? 'Framed Bookmark'));

        if ($uid === '' || $targetUrl === '') {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Missing UID or target URL.']), 400);
        }

        $existing = $this->tagRepository->findByUidOrSlug($uid);
        if ($existing === null || empty($existing['slug'])) {
            $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
            $redirectUrl = $prefix . '/admin/inventory/bind?bind_uid=' . rawurlencode($uid) . '&target_url=' . rawurlencode($targetUrl) . '&friendly_name=' . rawurlencode($friendlyName);
            return Response::json(json_encode([
                'status'       => 'requires_form',
                'message'      => 'Tag has no slug specified yet. Redirecting to setup form...',
                'redirect_url' => $redirectUrl
            ]), 200);
        }

        if ($existing['target_url'] === $targetUrl) {
            return Response::json(json_encode([
                'status'  => 'already_bound',
                'message' => 'This tag is already assigned to this exact destination.',
                'uid'     => $uid
            ]), 200);
        }

        $forceDuplicate = !empty($input['force_duplicate']);
        if (!$forceDuplicate) {
            $duplicates = $this->tagRepository->findTagsByTargetUrl($targetUrl, $uid);
            if (!empty($duplicates)) {
                return Response::json(json_encode([
                    'status'        => 'warn_duplicate',
                    'message'       => 'One or more existing tags already link to this destination URI.',
                    'existing_tags' => $duplicates
                ]), 200);
            }
        }

        $slug = $existing['slug'];
        $success = $this->tagRepository->save($uid, null, $friendlyName, 'active', $slug, $targetUrl);

        if ($success) {
            return Response::json(json_encode(['status' => 'success', 'uid' => $uid, 'target_url' => $targetUrl]), 200);
        }

        return Response::json(json_encode(['status' => 'error', 'message' => 'Database update failed.']), 500);
    }

    /**
     * Unlink a tag via POST API without redirecting
     */
    public function fastUnbind(array $params = [], string $basePath = ''): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_lifetime' => 2592000, 'gc_maxlifetime' => 2592000]);
        }

        if (empty($_SESSION['admin_logged_in']) && !Config::isEmergencyOverride()) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Unauthorized admin session.']), 401);
        }

        $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $uid = trim((string) ($input['uid'] ?? ''));

        if ($uid === '') {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Missing UID.']), 400);
        }

        $success = $this->tagRepository->unlinkPost($uid);
        if ($success) {
            return Response::json(json_encode(['status' => 'success', 'uid' => $uid]), 200);
        }

        return Response::json(json_encode(['status' => 'error', 'message' => 'Failed to unlink tag in database.']), 500);
    }

    /**
     * Check if any inventory chips already target a specific destination URI
     */
    public function checkDuplicateTarget(array $params = [], string $basePath = ''): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_lifetime' => 2592000, 'gc_maxlifetime' => 2592000]);
        }

        if (empty($_SESSION['admin_logged_in']) && !Config::isEmergencyOverride()) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Unauthorized admin session.']), 401);
        }

        $input = json_decode((string) file_get_contents('php://input'), true) ?? $_POST;
        $targetUrl = trim((string) ($input['target_url'] ?? ''));
        $excludeUid = trim((string) ($input['exclude_uid'] ?? ''));

        if ($targetUrl === '' || $targetUrl === 'http://' || $targetUrl === 'https://') {
            return Response::json(json_encode(['status' => 'success', 'has_duplicates' => false, 'duplicates' => []]), 200);
        }

        $duplicates = $this->tagRepository->findTagsByTargetUrl($targetUrl, $excludeUid);

        return Response::json(json_encode([
            'status'         => 'success',
            'has_duplicates' => !empty($duplicates),
            'duplicates'     => $duplicates
        ]), 200);
    }

    /**
     * Display historical tag activity audit feed and rollback console (GET /admin/history)
     */
    public function history(array $params = [], string $basePath = ''): Response
    {
        if (($redirect = $this->requireAuth($basePath)) !== null) {
            return $redirect;
        }

        $logs = $this->tagRepository->getHistory(null, 100);
        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/admin_history.php';

        ob_start();
        include $viewPath;
        return Response::html((string) ob_get_clean(), 200);
    }

    /**
     * Process one-click historical state rollback (POST /admin/inventory/revert)
     */
    public function revert(array $params = [], string $basePath = ''): Response
    {
        if (($redirect = $this->requireAuth($basePath)) !== null) {
            return $redirect;
        }

        $logId = (int) ($_POST['log_id'] ?? ($_GET['log_id'] ?? 0));
        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';

        if ($logId > 0 && $this->tagRepository->revertFromLog($logId)) {
            return Response::redirect($prefix . '/admin/history?reverted=1', 302);
        }

        return Response::redirect($prefix . '/admin/history?error=1', 302);
    }
}
