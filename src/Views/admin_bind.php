<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Simple Admin Tag Assignment & URL Binding View
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    exit('Direct access forbidden.');
}

// Ensure variables from AdminController exist
$uid = $uid ?? '';
$existing = $existing ?? null;

$slugParam = trim((string) ($_GET['slug'] ?? ''));
$nameParam = trim((string) ($_GET['friendly_name'] ?? ''));
$urlParam  = trim((string) ($_GET['target_url'] ?? ''));

$slugVal = htmlspecialchars((string) ($slugParam !== '' ? $slugParam : ($existing['slug'] ?? '')), ENT_QUOTES, 'UTF-8');
$nameVal = htmlspecialchars((string) ($nameParam !== '' ? $nameParam : ($existing['friendly_name'] ?? '')), ENT_QUOTES, 'UTF-8');
if ($urlParam !== '' && $urlParam !== 'https://') {
    $urlVal = htmlspecialchars($urlParam, ENT_QUOTES, 'UTF-8');
} else {
    $urlVal = htmlspecialchars((string) (!empty($existing['target_url']) ? $existing['target_url'] : 'https://'), ENT_QUOTES, 'UTF-8');
}

$formAction = ($basePath !== '' && $basePath !== '/' ? rtrim($basePath, '/') : '') . '/admin/inventory/bind';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign NFC Tag — Admin Console</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
<body class="text-slate-100 font-sans p-4 md:p-8 flex items-center justify-center min-h-screen">
    <main class="max-w-xl w-full mx-auto glass rounded-3xl p-6 md:p-8 space-y-6 border border-slate-700">
        
        <div class="border-b border-slate-800 pb-4 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider block">Admin Inventory Wizard</span>
                <h1 class="text-2xl font-bold text-white">Assign NFC Chip Target</h1>
            </div>
            <a href="javascript:history.back()" class="text-xs text-slate-400 hover:text-white px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 transition">&larr; Back</a>
        </div>

        <form id="bindForm" action="<?= htmlspecialchars($formAction, ENT_QUOTES) ?>" method="POST" class="space-y-5">
            
            <!-- Hardware UID (ReadOnly / Pre-filled) -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Hardware Chip UID / Serial</label>
                <input type="text" name="uid" value="<?= htmlspecialchars($uid, ENT_QUOTES) ?>" required readonly class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 font-mono text-white font-bold text-base select-all cursor-not-allowed opacity-100 focus:outline-none transition">
            </div>

            <!-- Friendly Slug with Auto-Gen -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Custom Friendly Slug (URL Address)</label>
                    <button type="button" onclick="generateSlug()" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold underline"><i class="fa-solid fa-bolt"></i> Auto-Generate</button>
                </div>
                <input type="text" id="slugInput" name="slug" value="<?= $slugVal ?>" placeholder="e.g. storage-bin-01 or favorite-item" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 font-mono text-sm text-slate-200 focus:border-indigo-500 focus:outline-none transition">
                <p class="text-slate-500 text-xs mt-1">Visiting <code class="text-slate-400">/your-slug</code> will act as the canonical redirector.</p>
            </div>

            <!-- Friendly Display Name (Optional) -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Item Title / Description</label>
                <input type="text" id="nameInput" name="friendly_name" value="<?= $nameVal ?>" placeholder="e.g. Top Garage Shelf Bin" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:border-indigo-500 focus:outline-none transition">
            </div>

            <!-- Destination Target URL -->
            <div>
                <label class="block text-xs font-semibold text-cyan-400 uppercase tracking-wider mb-1">Destination Target URL</label>
                <input type="url" name="target_url" value="<?= $urlVal ?>" required placeholder="https://example.com/resource" class="w-full bg-slate-950 border border-cyan-500/50 rounded-xl px-4 py-3 text-sm text-cyan-300 focus:border-cyan-400 focus:outline-none transition">
                <p class="text-slate-500 text-xs mt-1">Both the chip serial and friendly slug will issue an HTTP 302 redirect here.</p>
            </div>

            <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row gap-3">
                <button id="submitBtn" type="submit" class="flex-1 py-3.5 px-6 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 hover:brightness-110 active:scale-95 text-white font-bold text-sm shadow-lg transition">
                    Save Tag &amp; Test Link &rarr;
                </button>
            </div>

        </form>

    </main>

    <!-- Duplicate URI Confirmation Modal -->
    <div id="duplicateModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 opacity-0 transition-opacity duration-200">
        <div class="glass max-w-lg w-full rounded-3xl p-6 md:p-8 border border-amber-500/40 shadow-2xl shadow-amber-500/10 scale-95 transition-transform duration-200" id="duplicateModalBox">
            <div class="flex items-center gap-4 border-b border-slate-800 pb-4 mb-5">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-wider block">Duplicate URI Detected</span>
                    <h3 class="text-xl font-bold text-white">Confirm Multiple Assignment</h3>
                </div>
            </div>
            <p class="text-slate-300 text-sm leading-relaxed mb-4">
                The destination URI you entered is already linked to existing inventory tags in the system. Do you still wish to assign this chip to the exact same destination?
            </p>
            <div class="bg-slate-950/80 rounded-2xl p-4 border border-slate-800/80 mb-6 max-h-40 overflow-y-auto font-mono text-xs text-slate-400 space-y-2" id="existingTagsList">
            </div>
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeDuplicateModal(false)" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-sm transition">
                    <i class="fa-solid fa-xmark mr-1.5"></i> Cancel
                </button>
                <button type="button" onclick="closeDuplicateModal(true)" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:brightness-110 active:scale-95 text-white font-bold text-sm shadow-lg shadow-amber-500/20 transition">
                    <i class="fa-solid fa-check mr-1.5"></i> Proceed Anyway
                </button>
            </div>
        </div>
    </div>

    <script>
        function generateSlug() {
            const uid = "<?= htmlspecialchars($uid, ENT_QUOTES) ?>".replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
            const randomSuffix = Math.floor(100 + Math.random() * 900);
            const slug = "item-" + (uid ? uid.substring(uid.length - 4) : randomSuffix) + "-" + randomSuffix;
            document.getElementById('slugInput').value = slug;
            if (!document.getElementById('nameInput').value) {
                document.getElementById('nameInput').value = "Assigned Item " + randomSuffix;
            }
        }

        let duplicateResolve = null;
        function showDuplicateDialog(duplicates) {
            return new Promise((resolve) => {
                duplicateResolve = resolve;
                const modal = document.getElementById('duplicateModal');
                const box = document.getElementById('duplicateModalBox');
                const list = document.getElementById('existingTagsList');
                list.innerHTML = '';
                
                duplicates.forEach(d => {
                    const el = document.createElement('div');
                    el.className = 'flex items-center justify-between py-1 border-b border-slate-800/50 last:border-0';
                    el.innerHTML = `<span class="text-slate-200 font-bold">${d.friendly_name || 'Tag Item'}</span> <span class="text-indigo-400 text-[11px]">${d.uid || ''}</span>`;
                    list.appendChild(el);
                });
                
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    box.classList.remove('scale-95');
                }, 10);
            });
        }

        function closeDuplicateModal(confirmed) {
            const modal = document.getElementById('duplicateModal');
            const box = document.getElementById('duplicateModalBox');
            modal.classList.add('opacity-0');
            box.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                if (duplicateResolve) duplicateResolve(confirmed);
            }, 200);
        }

        let isConfirmedDuplicate = false;
        document.getElementById('bindForm').addEventListener('submit', async function(e) {
            if (isConfirmedDuplicate) return;
            
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const targetUrl = document.querySelector('input[name="target_url"]').value;
            const uid = document.querySelector('input[name="uid"]').value;
            
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Checking...';
            
            try {
                const resp = await fetch('/admin/api/check-duplicate-target', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ target_url: targetUrl, exclude_uid: uid })
                });
                const data = await resp.json();
                if (data.status === 'success' && data.has_duplicates) {
                    btn.innerHTML = 'Save Tag &amp; Test Link &rarr;';
                    const confirmed = await showDuplicateDialog(data.duplicates);
                    if (!confirmed) return;
                }
            } catch (err) {
                // Ignore network error on duplicate check and submit normally
            }
            
            isConfirmedDuplicate = true;
            btn.innerHTML = 'Saving...';
            this.submit();
        });
    </script>
</body>
</html>
