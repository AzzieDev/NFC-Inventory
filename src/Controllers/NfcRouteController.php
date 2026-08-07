<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * NFC Routing Engine Controller (GET /{tag_uid})
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Models\Tag;
use App\Utils\TagHelper;
use OpenApi\Attributes as OA;

class NfcRouteController
{
    private Tag $tagRepository;

    public function __construct(?Tag $tagRepository = null)
    {
        $this->tagRepository = $tagRepository ?? new Tag();
    }

    #[OA\Get(
        path: '/{tag_uid}',
        summary: 'NFC Routing Engine — Cascading resolution from physical tag serials -> friendly slugs -> target destination URLs',
        tags: ['NFC Routing'],
        parameters: [
            new OA\Parameter(
                name: 'tag_uid',
                in: 'path',
                required: true,
                description: 'Physical NFC chip hardware serial number (e.g. 04:6A:F1:A2) or customizable friendly slug (e.g. favorite-item-01)',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 302, 
                description: 'HTTP Redirect cascading from hardware UID to friendly slug, or from friendly slug to target destination URL / inventory record'
            ),
            new OA\Response(
                response: 200, 
                description: 'Generic Tag Unassigned UI if tag is currently unassigned in inventory'
            )
        ]
    )]
    public function resolveTag(string $rawUid, string $basePath = ''): Response
    {
        $rawUid = rawurldecode($rawUid);
        $normalizedUid = TagHelper::normalizeUid($rawUid);
        $isHardwareSerial = TagHelper::isHardwareSerial($normalizedUid);
        $displayUid = TagHelper::formatForDisplay($normalizedUid);

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        // Query database inventory by either hardware serial or custom friendly slug
        $tagRecord = $this->tagRepository->findByUidOrSlug($rawUid);

        if ($tagRecord !== null) {

            // Hop #1: If scanned by hardware UID and a customizable friendly slug exists, 302 redirect to the slug URL first!
            if (strcasecmp($normalizedUid, (string) $tagRecord['uid']) === 0 && !empty($tagRecord['slug'])) {
                // Ensure we don't end up in a circular loop if someone named their slug identical to the hex serial
                if (strcasecmp($normalizedUid, (string) $tagRecord['slug']) !== 0) {
                    return Response::redirect($prefix . '/' . $tagRecord['slug'], 302);
                }
            }

            // Hop #2a: If a custom external or explicit destination target_url is configured, issue 302 redirect there!
            if (!empty($tagRecord['target_url'])) {
                $target = (string) $tagRecord['target_url'];
                // If it is an internal root path (starting with /), attach optional Laragon base path prefix
                if (str_starts_with($target, '/') && !str_starts_with($target, '//')) {
                    $target = $prefix . $target;
                }
                return Response::redirect($target, 302);
            }

            // Hop #2b: If linked to an internal item post_id, issue HTTP 302 redirect to the record view
            if (!empty($tagRecord['post_id'])) {
                $postId = (int) $tagRecord['post_id'];
                return Response::redirect($prefix . '/post/' . $postId, 302);
            }
        }

        // 3. If unlinked or brand new tag, render the generic "Tag Unassigned" template
        $viewPath = (defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../..') . '/src/Views/unassigned.php';

        ob_start();
        include $viewPath;
        $htmlOutput = (string) ob_get_clean();

        return Response::html($htmlOutput, 200);
    }
}
