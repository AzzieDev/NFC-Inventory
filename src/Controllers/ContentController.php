<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Native Parsedown Content Data Service & Markdown Management Controller (/content & /admin/content)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Config;
use App\Http\Response;
use App\Models\Tag;

class ContentController
{
    private string $markdownDir;

    public function __construct()
    {
        $this->markdownDir = str_replace('\\', '/', (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/markdown');
        if (!is_dir($this->markdownDir)) {
            mkdir($this->markdownDir, 0755, true);
        }
    }

    /**
     * Guard verifying valid administrator authentication session or EMERGENCY_OVERRIDE
     */
    private function requireAuth(string $basePath): ?Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['admin_logged_in']) || Config::isEmergencyOverride()) {
            return null;
        }

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        return Response::redirect($prefix . '/login', 302);
    }

    /**
     * Validate against path traversal and return canonical absolute path to markdown file (supports spaces and folders)
     */
    private function resolvePath(string $slug): ?string
    {
        // Decode %20 and legacy '+' URL space encoding
        $slug = urldecode(str_replace('+', ' ', trim($slug, '/.')));
        if ($slug === '' || preg_match('#(?:^|/|\\\\)\.\.(?:/|\\\\|$)#', $slug) || str_contains($slug, "\0")) {
            return null;
        }
        return $this->markdownDir . '/' . $slug . '.md';
    }

    /**
     * Recursively find all markdown files in storage directory and return sorted by mtime descending
     */
    private function getMarkdownFiles(): array
    {
        $fileList = [];
        if (!is_dir($this->markdownDir)) {
            return [];
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->markdownDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                    $realPath = str_replace('\\', '/', $file->getPathname());
                    $baseDir = $this->markdownDir . '/';
                    $slug = substr($realPath, strlen($baseDir));
                    $slug = substr($slug, 0, -3); // Strip .md extension

                    $title = basename($slug);
                    $title = ucwords(str_replace(['-', '_'], ' ', $title));

                    $fileList[] = [
                        'path' => $file->getPathname(),
                        'mtime' => $file->getMTime(),
                        'slug' => $slug,
                        'title' => $title
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore filesystem access exceptions on unreadable temporary nodes
        }

        usort($fileList, static fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $fileList;
    }

    /**
     * Display date-reversed list of markdown items without showing dates (GET / or /content)
     */
    public function index(array $params = [], string $basePath = ''): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isAdmin = !empty($_SESSION['admin_logged_in']) || Config::isEmergencyOverride();
        $initialUrl = '/';

        $fileList = $this->getMarkdownFiles();

        // Search Filter
        $searchQuery = trim((string) ($_GET['q'] ?? ''));
        if ($searchQuery !== '') {
            $filtered = [];
            foreach ($fileList as $item) {
                $content = @file_get_contents($item['path']) ?: '';
                if (stripos($item['title'], $searchQuery) !== false || 
                    stripos($item['slug'], $searchQuery) !== false || 
                    stripos($content, $searchQuery) !== false) {
                    $filtered[] = $item;
                }
            }
            $fileList = $filtered;
        }

        // Pagination (10 items per page as requested for testing)
        $perPage = 10;
        $totalItems = count($fileList);
        $totalPages = (int) max(1, ceil($totalItems / $perPage));
        $currentPage = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset = ($currentPage - 1) * $perPage;

        $pagedFiles = array_slice($fileList, $offset, $perPage);
        $contentIndex = [];
        foreach ($pagedFiles as $item) {
            $contentIndex[] = [
                'slug' => $item['slug'],
                'title' => $item['title']
            ];
        }

        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/admin_browser.php';
        ob_start();
        include $viewPath;
        return Response::html((string) ob_get_clean(), 200);
    }

    /**
     * Render Parsedown HTML natively inside browser shell without iframes (GET /content/[*:slug])
     */
    public function view(array $params = [], string $basePath = ''): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isAdmin = !empty($_SESSION['admin_logged_in']) || Config::isEmergencyOverride();
        
        $slug = trim((string) ($params['slug'] ?? ''), '/');
        $filePath = $this->resolvePath($slug);
        if ($filePath === null || !is_file($filePath)) {
            return Response::redirect('/', 302);
        }

        // Canonical slug after decoding
        $canonicalSlug = urldecode(str_replace('+', ' ', trim($slug, '/.')));
        $rawMarkdown = (string) file_get_contents($filePath);
        $parsedown = new \Parsedown();
        $nativeHtml = $parsedown->text($rawMarkdown);
        $activeSlug = $canonicalSlug;
        $initialUrl = '/content/' . $canonicalSlug;

        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/admin_browser.php';
        ob_start();
        include $viewPath;
        return Response::html((string) ob_get_clean(), 200);
    }

    /**
     * Gist-style raw plain-text endpoint (GET /content/[*:slug]/raw)
     */
    public function raw(array $params = [], string $basePath = ''): Response
    {
        $slug = trim((string) ($params['slug'] ?? ''), '/');
        $filePath = $this->resolvePath($slug);
        if ($filePath === null || !is_file($filePath)) {
            return new Response(404, '404 File Not Found', ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $content = (string) file_get_contents($filePath);
        return new Response(200, $content, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /**
     * Interactive Desktop Markdown Editor with File Management (GET /admin/content/edit)
     */
    public function edit(array $params = [], string $basePath = ''): Response
    {
        if (($guard = $this->requireAuth($basePath)) !== null) {
            return $guard;
        }

        $slug = urldecode(str_replace('+', ' ', trim((string) ($_GET['item'] ?? ''), '/.')));
        $markdown = '';
        if ($slug !== '') {
            $filePath = $this->resolvePath($slug);
            if ($filePath !== null && is_file($filePath)) {
                $markdown = (string) file_get_contents($filePath);
            }
        }

        $allFiles = $this->getMarkdownFiles();
        $searchQuery = trim((string) ($_GET['q'] ?? ''));

        if ($searchQuery !== '') {
            $filtered = [];
            foreach ($allFiles as $item) {
                $content = @file_get_contents($item['path']) ?: '';
                if (stripos($item['title'], $searchQuery) !== false || 
                    stripos($item['slug'], $searchQuery) !== false || 
                    stripos($content, $searchQuery) !== false) {
                    $filtered[] = $item;
                }
            }
            $fileList = $filtered;
        } else {
            $fileList = $allFiles;
        }

        // Ensure currently open file is always selected and moved to top of results even if it didn't match search
        if ($slug !== '') {
            $activeIndex = null;
            foreach ($fileList as $idx => $item) {
                if (strcasecmp((string)$item['slug'], (string)$slug) === 0) {
                    $activeIndex = $idx;
                    break;
                }
            }
            if ($activeIndex !== null) {
                $activeItem = $fileList[$activeIndex];
                unset($fileList[$activeIndex]);
            } else {
                // If open file didn't match search query, look in $allFiles or construct descriptor
                $activeItem = [
                    'slug' => $slug,
                    'title' => ucwords(str_replace(['-', '_'], ' ', basename($slug))),
                    'path' => $this->markdownDir . '/' . $slug . '.md'
                ];
                foreach ($allFiles as $orig) {
                    if (strcasecmp((string)$orig['slug'], (string)$slug) === 0) {
                        $activeItem = $orig;
                        break;
                    }
                }
            }
            array_unshift($fileList, $activeItem);
            $fileList = array_values($fileList);
        }

        // Pagination for editor sidebar (10 items per page)
        $perPage = 10;
        $totalItems = count($fileList);
        $totalPages = (int) max(1, ceil($totalItems / $perPage));
        $currentPage = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset = ($currentPage - 1) * $perPage;

        $fileList = array_slice($fileList, $offset, $perPage);

        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/admin_content_edit.php';
        ob_start();
        include $viewPath;
        return Response::html((string) ob_get_clean(), 200);
    }

    /**
     * API: Save markdown item and handle renaming if original_slug changed (POST /admin/api/content/save)
     */
    public function save(array $params = [], string $basePath = ''): Response
    {
        if (($guard = $this->requireAuth($basePath)) !== null) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Unauthorized admin session.']), 401);
        }

        $input = json_decode((string) file_get_contents('php://input'), true) ?? $_POST;
        
        $rawSlug = urldecode(str_replace('+', ' ', trim((string) ($input['slug'] ?? ''), '/')));
        // Allow spaces and normal words; strip only directory traversal and illegal filesystem characters
        $slug = preg_replace('/[\x00-\x1F\x7F\\\\:*?"<>|]|(?:^|\/)\.\.(?:$|\/)/', '', $rawSlug);
        $slug = trim((string) $slug, '/.');
        
        $markdown = (string) ($input['markdown'] ?? '');
        $originalSlug = urldecode(str_replace('+', ' ', trim((string) ($input['original_slug'] ?? ''), '/.')));

        if ($slug === '') {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Valid filename / slug is required.']), 400);
        }

        $filePath = $this->markdownDir . '/' . $slug . '.md';
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        if (file_put_contents($filePath, $markdown) === false) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Failed to write markdown file to storage.']), 500);
        }

        // Handle file renaming: delete old file if original slug was modified
        if ($originalSlug !== '' && strcasecmp($originalSlug, $slug) !== 0) {
            $oldPath = $this->resolvePath($originalSlug);
            if ($oldPath !== null && is_file($oldPath) && strcasecmp(realpath($oldPath), realpath($filePath)) !== 0) {
                @unlink($oldPath);
                // Attempt to clean empty directory if old file was in a subfolder
                @rmdir(dirname($oldPath));
            }
            try {
                $tagModel = new Tag();
                $tagModel->updateTargetUrlReferences($originalSlug, $slug);
            } catch (\Exception $e) {
                // Ignore DB error if connection unavailable during test runs
            }
        }

        $encodedSlug = str_replace('%2F', '/', rawurlencode($slug));

        return Response::json(json_encode([
            'status' => 'success',
            'slug' => $slug,
            'url' => '/content/' . $encodedSlug,
            'raw_url' => '/content/' . $encodedSlug . '/raw'
        ]), 200);
    }

    /**
     * API: Delete markdown item (POST /admin/api/content/delete)
     */
    public function delete(array $params = [], string $basePath = ''): Response
    {
        if (($guard = $this->requireAuth($basePath)) !== null) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Unauthorized admin session.']), 401);
        }

        $input = json_decode((string) file_get_contents('php://input'), true) ?? $_POST;
        $slug = urldecode(str_replace('+', ' ', trim((string) ($input['slug'] ?? ''), '/.')));
        $filePath = $this->resolvePath($slug);

        if ($filePath === null || !is_file($filePath)) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'File not found on disk.']), 404);
        }

        if (@unlink($filePath) === false) {
            return Response::json(json_encode(['status' => 'error', 'message' => 'Failed to delete file from disk.']), 500);
        }

        @rmdir(dirname($filePath));

        return Response::json(json_encode(['status' => 'success']), 200);
    }

    /**
     * API: Real-time markdown HTML rendering preview (POST /api/v1/markdown/preview)
     */
    public function preview(array $params = [], string $basePath = ''): Response
    {
        $input = json_decode((string) file_get_contents('php://input'), true) ?? $_POST;
        $markdown = (string) ($input['markdown'] ?? '');
        
        $parsedown = new \Parsedown();
        return Response::json(json_encode([
            'status' => 'success',
            'html' => $parsedown->text($markdown)
        ]), 200);
    }
}
