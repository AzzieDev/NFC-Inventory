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
$slugVal = htmlspecialchars((string) ($existing['slug'] ?? ''), ENT_QUOTES);
$nameVal = htmlspecialchars((string) ($existing['friendly_name'] ?? ''), ENT_QUOTES);
$urlVal  = htmlspecialchars((string) ($existing['target_url'] ?? ($_GET['target_url'] ?? 'https://')), ENT_QUOTES);

$formAction = ($basePath !== '' && $basePath !== '/' ? rtrim($basePath, '/') : '') . '/admin/inventory/bind';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign NFC Tag — Admin Console</title>
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
<body class="text-slate-100 font-sans p-4 md:p-8 flex items-center justify-center min-h-screen">
    <main class="max-w-xl w-full mx-auto glass rounded-3xl p-6 md:p-8 space-y-6 border border-slate-700">
        
        <div class="border-b border-slate-800 pb-4 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider block">Admin Inventory Wizard</span>
                <h1 class="text-2xl font-bold text-white">Assign NFC Chip Target</h1>
            </div>
            <a href="javascript:history.back()" class="text-xs text-slate-400 hover:text-white px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 transition">&larr; Back</a>
        </div>

        <form action="<?= htmlspecialchars($formAction, ENT_QUOTES) ?>" method="POST" class="space-y-5">
            
            <!-- Hardware UID (ReadOnly / Pre-filled) -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Hardware Chip UID / Serial</label>
                <input type="text" name="uid" value="<?= htmlspecialchars($uid, ENT_QUOTES) ?>" required readonly class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 font-mono text-white font-bold text-base select-all cursor-not-allowed opacity-100 focus:outline-none transition">
            </div>

            <!-- Friendly Slug with Auto-Gen -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Custom Friendly Slug (URL Address)</label>
                    <button type="button" onclick="generateSlug()" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold underline">⚡ Auto-Generate</button>
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
                <button type="submit" class="flex-1 py-3.5 px-6 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 hover:brightness-110 active:scale-95 text-white font-bold text-sm shadow-lg transition">
                    Save Tag &amp; Test Link &rarr;
                </button>
            </div>

        </form>

    </main>

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
    </script>
</body>
</html>
