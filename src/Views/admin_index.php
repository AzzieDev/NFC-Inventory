<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Admin Console & Tag Binding Dashboard View
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    exit('Direct access forbidden.');
}

$prefix = isset($basePath) && $basePath !== '' && $basePath !== '/' ? rtrim($basePath, '/') : '';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Inventory — NFC State Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                }
            }
        }
    </script>
    <style>
        body { background-color: #0b0d14; min-height: 100vh; }
        .glass { background: rgba(18, 22, 33, 0.95); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.7); }
        @keyframes pulse-ring {
            0% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(99, 102, 241, 0); }
            100% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }
        .animate-pulse-ring { animation: pulse-ring 2s infinite; }
    </style>
</head>
<body class="text-slate-100 font-sans p-4 md:p-8 min-h-screen">
    <main class="max-w-6xl mx-auto glass rounded-3xl p-6 md:p-8 space-y-6 border border-slate-700">
        
        <!-- Header -->
        <div class="border-b border-slate-800 pb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider block">Phase 2 Command Center</span>
                <h1 class="text-2xl font-bold text-white">NFC Chip Inventory Catalog</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="/admin/content/edit" class="text-xs text-emerald-300 hover:text-white px-4 py-2.5 rounded-xl bg-emerald-950/60 border border-emerald-500/40 transition font-bold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-pen-to-square"></i> <span>MD Editor</span>
                </a>
                <a href="/browse" class="text-xs text-indigo-300 hover:text-white px-4 py-2.5 rounded-xl bg-indigo-950/60 border border-indigo-500/40 transition font-bold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-globe"></i> <span>iFrame Browser</span>
                </a>
                <a href="<?= htmlspecialchars($prefix . '/admin/history', ENT_QUOTES) ?>" class="text-xs text-amber-300 hover:text-white px-4 py-2.5 rounded-xl bg-amber-950/50 border border-amber-500/30 transition font-bold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i> <span>Activity Logs</span>
                </a>
                <a href="<?= htmlspecialchars($prefix === '' ? '/' : $prefix, ENT_QUOTES) ?>" class="text-xs text-slate-300 hover:text-white px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-600 transition font-semibold">
                    &larr; Home
                </a>
                <button id="adminScanBtn" onclick="triggerAdminNfcScan()" class="text-xs text-white px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 hover:brightness-110 font-bold transition shadow-lg flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-wifi"></i> <span>Tap to Assign or Edit Tag</span>
                </button>
                <a href="<?= htmlspecialchars($prefix . '/logout', ENT_QUOTES) ?>" class="text-xs text-red-400 hover:text-red-300 px-3 py-2.5 rounded-xl bg-red-500/10 border border-red-500/20 transition font-semibold">
                    Logout
                </a>
            </div>
        </div>

        <!-- Inline NFC Scanner Feedback Alert -->
        <div id="adminScanFeedback" class="hidden p-4 rounded-2xl bg-indigo-950/70 border border-indigo-500/40 flex items-center justify-between gap-3 animate-pulse">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full bg-cyan-400 animate-ping"></span>
                <span id="adminScanText" class="text-xs md:text-sm font-semibold text-slate-200">
                    ⚡ Admin Scanner Armed! Tap any physical NFC tag against your phone right now to open its assignment and configuration form...
                </span>
            </div>
            <button onclick="document.getElementById('adminScanFeedback').classList.add('hidden')" class="text-xs text-slate-400 hover:text-white px-2 py-1 bg-slate-800 rounded-lg">Cancel</button>
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
                                No NFC tags have been assigned or scanned into inventory yet. Click "Tap to Assign or Edit Tag" to get started!
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
                                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                    <a 
                                        href="<?= htmlspecialchars($prefix . '/admin/inventory/bind?uid=' . rawurlencode($uid), ENT_QUOTES) ?>"
                                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-600/20 text-indigo-300 hover:bg-indigo-600 hover:text-white border border-indigo-500/30 transition inline-block"
                                    >
                                        Edit / Re-bind &rarr;
                                    </a>
                                    <a 
                                        href="<?= htmlspecialchars($prefix . '/admin/inventory/delete?uid=' . rawurlencode($uid), ENT_QUOTES) ?>"
                                        onclick="return confirm('Are you sure you want to permanently delete this tag record (<?= htmlspecialchars(addslashes($uid), ENT_QUOTES) ?>)?');"
                                        title="Permanently remove tag"
                                        class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-600 hover:text-white border border-red-500/20 transition inline-block"
                                    >
                                        🗑️ Delete
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
        // Admin Direct In-Place NFC Binding Trigger
        async function triggerAdminNfcScan() {
            const prefix = "<?= htmlspecialchars($prefix, ENT_QUOTES) ?>";
            const feedback = document.getElementById('adminScanFeedback');
            const feedbackText = document.getElementById('adminScanText');
            const btn = document.getElementById('adminScanBtn');

            // On desktop or non-Android browsers without Web NFC, transition directly to Home Scanner in Admin Bind Mode
            if (!('NDEFReader' in window)) {
                window.location.href = (prefix || '') + '/?mode=bind';
                return;
            }

            try {
                const ndef = new NDEFReader();
                await ndef.scan();
                
                feedback.classList.remove('hidden');
                btn.classList.add('animate-pulse-ring');
                btn.innerHTML = '<span>⚡ Listening... Touch Tag Now</span>';

                ndef.addEventListener("reading", ({ serialNumber }) => {
                    feedbackText.innerHTML = '✅ Tag Scanned (<span class="font-mono text-cyan-400">' + serialNumber + '</span>). Opening configuration form...';
                    btn.classList.remove('animate-pulse-ring');
                    
                    // Directly transition to admin bind form regardless of whether tag exists or is unassigned!
                    setTimeout(() => {
                        window.location.href = (prefix || '') + '/admin/inventory/bind?uid=' + encodeURIComponent(serialNumber);
                    }, 400);
                });

                ndef.addEventListener("error", () => {
                    feedbackText.innerHTML = '❌ Failed to read NFC chip. Please hold your phone steady and try tapping again.';
                });

            } catch (error) {
                // If permission denied or unavailable, cleanly fallback to home bind mode
                window.location.href = (prefix || '') + '/?mode=bind';
            }
        }

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
