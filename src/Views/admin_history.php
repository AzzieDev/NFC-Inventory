<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Tag Activity Audit Trail & One-Click Reversals Console
 *
 * @var array<int, array> $logs
 * @var string $basePath
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

$logs = $logs ?? [];
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activity & Reversal Logs — NFC Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0f1117; color: #e2e8f0; font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header Controls -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-800 pb-6 mb-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> <span>Tag Activity & Rollback Console</span>
                </h1>
                <p class="text-sm text-gray-400 mt-1">Audit log of tag assignments, updates, deletions, and instantaneous historical reversions.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-sm shadow transition flex items-center gap-2">
                    <i class="fa-solid fa-globe"></i> <span>Browser</span>
                </a>
                <a href="/admin" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-200 font-semibold rounded-lg text-sm border border-gray-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> <span>Inventory Table</span>
                </a>
            </div>
        </div>

        <?php if (!empty($_GET['reverted'])): ?>
            <div class="mb-6 p-4 bg-emerald-950/40 border border-emerald-500/30 rounded-lg flex items-center gap-3 text-emerald-400 font-medium">
                <i class="fa-solid fa-circle-check text-xl"></i>
                <span>Tag state successfully restored from historical archive!</span>
            </div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-rose-950/40 border border-rose-500/30 rounded-lg flex items-center gap-3 text-rose-400 font-medium">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                <span>Failed to revert tag state. Log record may be invalid or missing.</span>
            </div>
        <?php endif; ?>

        <!-- Activity Timeline Table -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden shadow-lg">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800/80 text-gray-400 uppercase text-xs tracking-wider border-b border-gray-800">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Timestamp</th>
                            <th class="py-3.5 px-4 font-semibold">Tag UID</th>
                            <th class="py-3.5 px-4 font-semibold">Action</th>
                            <th class="py-3.5 px-4 font-semibold">Previous State</th>
                            <th class="py-3.5 px-4 font-semibold">New State</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Rollback</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60 text-gray-300">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500 italic">
                                    No activity logs recorded yet. Assign or modify NFC tags in the Browser or Inventory dashboard to populate this feed.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                    $action = strtolower((string) ($log['action_type'] ?? 'unknown'));
                                    $badgeColor = match ($action) {
                                        'assigned'   => 'bg-blue-900/50 text-blue-300 border-blue-500/30',
                                        'updated'    => 'bg-purple-900/50 text-purple-300 border-purple-500/30',
                                        'deleted', 'unassigned' => 'bg-rose-900/50 text-rose-300 border-rose-500/30',
                                        'reverted'   => 'bg-emerald-900/50 text-emerald-300 border-emerald-500/30',
                                        default      => 'bg-gray-800 text-gray-300 border-gray-700'
                                    };
                                ?>
                                <tr class="hover:bg-gray-800/40 transition">
                                    <td class="py-3 px-4 text-xs font-mono text-gray-400 whitespace-nowrap">
                                        <?= htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-white whitespace-nowrap">
                                        <?= htmlspecialchars((string) ($log['tag_uid'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $badgeColor ?>">
                                            <?= htmlspecialchars(strtoupper($action), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs max-w-xs truncate text-gray-400">
                                        <?php if (!empty($log['old_target_url'])): ?>
                                            <span class="font-semibold text-gray-300"><?= htmlspecialchars((string) ($log['old_friendly_name'] ?? 'Tag'), ENT_QUOTES, 'UTF-8') ?>:</span>
                                            <code class="text-indigo-400"><?= htmlspecialchars((string) $log['old_target_url'], ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php else: ?>
                                            <span class="text-gray-600 italic">None / Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-xs max-w-xs truncate text-gray-300">
                                        <?php if (!empty($log['new_target_url'])): ?>
                                            <span class="font-semibold text-gray-200"><?= htmlspecialchars((string) ($log['new_friendly_name'] ?? 'Tag'), ENT_QUOTES, 'UTF-8') ?>:</span>
                                            <code class="text-emerald-400"><?= htmlspecialchars((string) $log['new_target_url'], ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php else: ?>
                                            <span class="text-gray-600 italic">Unassigned / Deleted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <?php if (!empty($log['old_target_url']) && $action !== 'reverted'): ?>
                                            <form action="inventory/revert" method="POST" class="inline-block" onsubmit="return confirm('Restore tag <?= htmlspecialchars((string) $log['tag_uid'], ENT_QUOTES) ?> back to this previous state?');">
                                                <input type="hidden" name="log_id" value="<?= (int) $log['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1.5 bg-amber-600/20 hover:bg-amber-600 text-amber-300 hover:text-white border border-amber-500/40 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5">
                                                    <i class="fa-solid fa-rotate-left"></i> <span>Revert</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-600 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
