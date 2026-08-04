<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Admin Inventory Table Overview & Slug Copy Dashboard
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    exit('Direct access forbidden.');
}

$tags = $tags ?? [];
$prefix = ($basePath !== '' && $basePath !== '/' ? rtrim($basePath, '/') : '');
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard — Admin Console</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'], mono: ['"JetBrains Mono"', 'monospace'] } } }
        }
    </script>
    <style>
        body { background-color: #0b0d14; min-height: 100vh; }
        .glass { background: rgba(18, 22, 33, 0.95); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.7); }
    </style>
</head>
<body class="text-slate-100 font-sans p-4 md:p-8 min-h-screen">
    <main class="max-w-6xl mx-auto glass rounded-3xl p-6 md:p-8 space-y-6 border border-slate-700">
        
        <!-- Header -->
        <div class="border-b border-slate-800 pb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider block">Phase 2 Command Center</span>
                <h1 class="text-2xl font-bold text-white">NFC Chip Inventory Catalog</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= htmlspecialchars($prefix === '' ? '/' : $prefix, ENT_QUOTES) ?>" class="text-xs text-slate-300 hover:text-white px-4 py-2 rounded-xl bg-slate-800 border border-slate-600 transition font-semibold">
                    &larr; Home Dashboard
                </a>
                <a href="<?= htmlspecialchars($prefix === '' ? '/' : $prefix, ENT_QUOTES) ?>" class="text-xs text-white px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 hover:brightness-110 font-bold transition shadow-lg flex items-center gap-1.5">
                    📡 Tap to Assign New Tag
                </a>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-900/90 text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800">
                        <th class="py-3.5 px-4 font-semibold">Hardware UID</th>
                        <th class="py-3.5 px-4 font-semibold">Item Title / Name</th>
                        <th class="py-3.5 px-4 font-semibold">Friendly Slug (Copy Link)</th>
                        <th class="py-3.5 px-4 font-semibold">Destination Target URL</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                        <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-200">
                    <?php if (empty($tags)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 text-sm">
                                No NFC tags have been assigned or scanned into inventory yet. Tap a chip on the home screen!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tags as $tag): ?>
                            <?php 
                                $slug = (string) ($tag['slug'] ?? '');
                                $uid  = (string) $tag['uid'];
                                $name = (string) ($tag['friendly_name'] ?? '—');
                                $url  = (string) ($tag['target_url'] ?? '—');
                                $status = (string) ($tag['status'] ?? 'available');
                            ?>
                            <tr class="hover:bg-slate-900/50 transition">
                                <td class="py-3.5 px-4 font-mono text-cyan-400 font-bold whitespace-nowrap">
                                    <?= htmlspecialchars($uid, ENT_QUOTES) ?>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-200">
                                    <?= htmlspecialchars($name, ENT_QUOTES) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($slug !== ''): ?>
                                        <div class="flex items-center gap-2">
                                            <code class="text-xs bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 px-2 py-1 rounded font-mono">
                                                /<?= htmlspecialchars($slug, ENT_QUOTES) ?>
                                            </code>
                                            <button 
                                                onclick="copySlugLink('<?= htmlspecialchars($slug, ENT_QUOTES) ?>', this)"
                                                title="Copy absolute slug URL to clipboard"
                                                class="text-xs text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-600 px-2.5 py-1 rounded-lg transition whitespace-nowrap flex items-center gap-1"
                                            >
                                                📋 Copy Link
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-600 italic text-xs">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-300 truncate max-w-[200px]" title="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                                    <?php if ($url !== '—'): ?>
                                        <a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" target="_blank" class="hover:text-cyan-400 underline"><?= htmlspecialchars($url, ENT_QUOTES) ?></a>
                                    <?php else: ?>
                                        <span class="text-slate-600">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider <?= $status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' ?>">
                                        <?= htmlspecialchars($status, ENT_QUOTES) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <a 
                                        href="<?= htmlspecialchars($prefix . '/admin/inventory?bind=' . rawurlencode($uid), ENT_QUOTES) ?>"
                                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-600/20 text-indigo-300 hover:bg-indigo-600 hover:text-white border border-indigo-500/30 transition inline-block"
                                    >
                                        Edit / Re-bind &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <script>
        function copySlugLink(slug, btnElement) {
            const origin = window.location.origin;
            const prefix = "<?= htmlspecialchars($prefix, ENT_QUOTES) ?>";
            const absoluteUrl = origin + prefix + '/' + slug;

            navigator.clipboard.writeText(absoluteUrl).then(() => {
                const originalText = btnElement.innerHTML;
                btnElement.innerHTML = '✅ Copied!';
                btnElement.classList.remove('text-slate-400');
                btnElement.classList.add('text-emerald-400');
                
                setTimeout(() => {
                    btnElement.innerHTML = originalText;
                    btnElement.classList.remove('text-emerald-400');
                    btnElement.classList.add('text-slate-400');
                }, 2000);
            }).catch(err => {
                alert('Could not copy link: ' + err);
            });
        }
    </script>
</body>
</html>
