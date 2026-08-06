<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Primary Landing Page (/) & Mobile-First Hybrid Native/iFrame Browser Shell (/content & /browse)
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

$isAdmin = $isAdmin ?? false;
$contentIndex = $contentIndex ?? null;
$nativeHtml = $nativeHtml ?? null;
$activeSlug = $activeSlug ?? null;
$searchQuery = $searchQuery ?? '';
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$totalItems = $totalItems ?? 0;

$initialUrl = $initialUrl ?? trim((string) ($_GET['p'] ?? '/'));
if ($initialUrl === '' || $initialUrl === '/markdown-blog' || $initialUrl === './markdown-blog' || $initialUrl === '/blog' || $initialUrl === '/content') {
    $initialUrl = '/';
}

$isNativeView = ($contentIndex !== null || $nativeHtml !== null);
?>
<!DOCTYPE html>
<html lang="en" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>NFC Inventory &amp; State Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        }
    </script>
    <style>
        body { margin: 0; padding: 0; background: #0f1117; color: #e2e8f0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .prose h1 { color: #f8fafc; font-size: 1.875rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; }
        .prose h2 { color: #f1f5f9; font-size: 1.5rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        .prose h3 { color: #e2e8f0; font-size: 1.25rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .prose p { margin-bottom: 1.25rem; line-height: 1.75; color: #cbd5e1; }
        .prose ul, .prose ol { margin-left: 1.5rem; margin-bottom: 1.25rem; list-style-type: disc; color: #cbd5e1; }
        .prose li { margin-bottom: 0.5rem; }
        .prose a { color: #818cf8; text-decoration: underline; }
        .prose a:hover { color: #a5b4fc; }
        .prose blockquote { border-left: 4px solid #4f46e5; padding-left: 1rem; color: #94a3b8; font-style: italic; background: #1e293b; padding: 0.75rem 1rem; border-radius: 0 0.5rem 0.5rem 0; margin-bottom: 1.25rem; }
        .prose code { background: #1e293b; color: #f8fafc; padding: 0.2rem 0.4rem; border-radius: 0.375rem; font-family: monospace; font-size: 0.9em; }
        .prose pre { background: #0b0d14; padding: 1rem; border-radius: 0.75rem; border: 1px solid #334155; overflow-x: auto; margin-bottom: 1.5rem; }
        .prose pre code { background: transparent; padding: 0; }
        .prose table { width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 1.5rem; }
        .prose th, .prose td { border: 1px solid #334155; padding: 0.75rem; }
        .prose th { background: #1e293b; color: #f8fafc; font-weight: 600; }
        @keyframes pulse-ring {
            0% { transform: scale(0.97); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(99, 102, 241, 0); }
            100% { transform: scale(0.97); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }
        .animate-pulse-ring { animation: pulse-ring 2s infinite; }
    </style>
</head>
<body class="h-full flex flex-col overflow-hidden">
    <!-- Uniform Touchscreen Header Toolbar -->
    <header class="h-14 bg-gray-900 border-b border-gray-800 px-2 sm:px-3 flex items-center gap-1 sm:gap-1.5 shrink-0 z-20 shadow-lg">
        <button onclick="goHome()" title="Home (/)" class="h-9 w-9 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 rounded-lg text-gray-300 hover:text-white border border-gray-700 transition flex items-center justify-center shrink-0 shadow-sm">
            <i class="fa-solid fa-house"></i>
        </button>

        <input id="urlInput" 
               type="text" 
               value="<?= htmlspecialchars($initialUrl, ENT_QUOTES, 'UTF-8') ?>" 
               placeholder="/" 
               class="h-9 flex-grow w-0 bg-gray-950 border border-gray-700 rounded-lg px-2.5 sm:px-3 text-xs sm:text-sm text-gray-200 focus:outline-none focus:border-indigo-500 truncate font-mono shadow-inner transition" />

        <button onclick="toggleInvertFilter()" id="darkToggleBtn" title="Toggle Universal Dark Filter" class="h-9 w-9 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 text-gray-300 hover:text-white rounded-lg border border-gray-700 transition flex items-center justify-center shrink-0 shadow-sm">
            <i class="fa-solid fa-circle-half-stroke"></i>
        </button>

        <?php if ($isAdmin): ?>
            <button onclick="openEditor()" title="Edit Markdown Item in Desktop Editor" class="h-9 w-9 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 text-gray-300 hover:text-white rounded-lg border border-gray-700 transition flex items-center justify-center shrink-0 shadow-sm">
                <i class="fa-solid fa-pen"></i>
            </button>

            <button id="updateTagBtn" onclick="fastAssignTag()" title="One-Tap Assign Current Page to Tag" class="h-9 px-2.5 sm:px-3.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold rounded-lg text-xs sm:text-sm whitespace-nowrap shadow flex items-center justify-center gap-1.5 shrink-0 transition border border-indigo-500">
                <i class="fa-solid fa-wifi"></i> <span class="hidden sm:inline">Update Tag</span>
            </button>

            <a href="/admin" title="Admin Inventory Dashboard" class="h-9 w-9 sm:w-auto sm:px-3 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 text-gray-300 hover:text-white rounded-lg border border-gray-700 transition flex items-center justify-center gap-1.5 shrink-0 text-xs sm:text-sm font-medium shadow-sm">
                <i class="fa-solid fa-gauge-high"></i><span class="hidden md:inline">Dashboard</span>
            </a>
        <?php else: ?>
            <a href="/login" title="Login for Admin Controls" class="h-9 px-3 sm:px-3.5 bg-indigo-950 hover:bg-indigo-900 text-indigo-300 hover:text-white border border-indigo-500/50 rounded-lg transition flex items-center justify-center gap-1.5 shrink-0 text-xs sm:text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-right-to-bracket"></i><span class="hidden xs:inline">Login</span>
            </a>
        <?php endif; ?>
    </header>

    <!-- Full-Screen Hybrid Workspace -->
    <main id="mainWorkspace" class="flex-grow w-full h-[calc(100vh-3.5rem)] relative bg-[#0f1117] overflow-y-auto">
        <?php if ($isNativeView): ?>
            <!-- NATIVE RENDERING -->
            <?php if ($contentIndex !== null): ?>
                <!-- Root Landing Page Catalog -->
                <div class="max-w-4xl mx-auto p-4 sm:p-10 text-gray-200">
                    
                    <!-- Title Bar -->
                    <div class="flex items-center justify-between pb-5 border-b border-gray-800 mb-6">
                        <h1 class="text-xl sm:text-3xl font-extrabold text-white flex items-center gap-3">
                            <i class="fa-solid fa-layer-group text-indigo-500"></i>
                            <span>NFC Inventory</span>
                        </h1>
                        <?php if ($isAdmin): ?>
                            <a href="/admin/content/edit" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs sm:text-sm shadow transition flex items-center gap-1.5 shrink-0">
                                <i class="fa-solid fa-plus"></i> <span>New Item</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- In-Page Web NFC Hardware Scanner Feature -->
                    <div onclick="startInPageTagScan()" class="mb-8 p-5 sm:p-6 rounded-2xl bg-gray-950/80 hover:bg-gray-950 border border-gray-800 hover:border-indigo-500/60 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-5 text-center sm:text-left cursor-pointer transition-all duration-200 group active:scale-[0.99]" title="Tap anywhere to start NFC scanner">
                        <div class="space-y-1 select-none">
                            <div class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center justify-center sm:justify-start gap-2">
                                <span id="nfcSensorDot" class="w-2.5 h-2.5 rounded-full bg-gray-600 inline-block"></span>
                                <span id="nfcSensorText">Web NFC Hardware Scanner Standby</span>
                            </div>
                            <p class="text-sm text-gray-300">Tap a physical NFC chip against your mobile device to immediately read and follow its assigned link or data record.</p>
                            <p id="inPageScanFeedback" class="text-xs text-emerald-400 font-mono pt-1 hidden animate-pulse"></p>
                        </div>
                        <button id="inPageScanBtn" type="button" class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 group-hover:brightness-110 active:scale-95 text-white font-bold text-sm sm:text-base shadow-lg transition flex items-center justify-center gap-2.5 shrink-0 pointer-events-none sm:pointer-events-auto">
                            <i class="fa-solid fa-wifi"></i> <span>Scan Tag</span>
                        </button>
                    </div>

                    <!-- Search & Filter Bar -->
                    <form action="/" method="get" class="mb-5 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <div class="relative flex-grow">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500"></i>
                            <input type="text" 
                                   name="q" 
                                   value="<?= htmlspecialchars($searchQuery ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   placeholder="Search catalog by title, slug, or contents..." 
                                   class="w-full pl-10 pr-4 py-2.5 bg-gray-950 border border-gray-800 rounded-xl text-sm sm:text-base text-gray-200 focus:outline-none focus:border-indigo-500 shadow-inner transition" />
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="submit" class="flex-1 sm:flex-initial px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-sm transition shadow flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-filter"></i> <span>Filter</span>
                            </button>
                            <?php if (!empty($searchQuery)): ?>
                                <a href="/" title="Clear Search Filter" class="px-4 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-xl text-sm font-semibold transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-xmark"></i> <span>Clear</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Content List Table (Left-Aligned, Truncated via Ellipsis on Mobile) -->
                    <?php if (empty($contentIndex)): ?>
                        <div class="text-center py-16 text-gray-500 bg-gray-950/40 rounded-2xl border border-gray-800/80">
                            <i class="fa-solid fa-folder-open text-4xl mb-3 block opacity-50"></i>
                            <p class="text-base font-medium">No records matching criteria found in storage.</p>
                            <?php if (!empty($searchQuery)): ?>
                                <p class="text-sm mt-2"><a href="/" class="text-indigo-400 hover:underline">Clear search filters to view all documents</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-800/80 border border-gray-800 rounded-2xl bg-gray-950/60 overflow-hidden shadow-xl">
                            <?php foreach ($contentIndex as $item): ?>
                                <?php $encodedSlug = str_replace('%2F', '/', rawurlencode($item['slug'])); ?>
                                <a href="/content/<?= $encodedSlug ?>" class="block px-4 py-3.5 sm:px-6 sm:py-4 hover:bg-gray-800/60 active:bg-gray-800 transition group w-full overflow-hidden">
                                    <div class="flex items-center gap-3.5 text-left w-full min-w-0">
                                        <i class="fa-regular fa-file-lines text-indigo-400/80 group-hover:text-indigo-300 shrink-0 text-base sm:text-lg"></i>
                                        <span class="text-sm sm:text-lg font-semibold text-gray-200 group-hover:text-indigo-300 truncate block min-w-0 flex-1"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- 10-Item Pagination Controls -->
                        <?php if ($totalPages > 1): ?>
                            <div class="mt-5 flex items-center justify-between text-xs sm:text-sm font-semibold text-gray-400 bg-gray-950/80 p-3.5 rounded-xl border border-gray-800 shadow">
                                <div>
                                    <?php if ($currentPage > 1): ?>
                                        <a href="/?page=<?= ($currentPage - 1) ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition inline-flex items-center gap-1.5 shadow-sm">
                                            <i class="fa-solid fa-chevron-left text-xs"></i> <span>Prev</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="px-4 py-2 bg-gray-900/80 text-gray-600 rounded-lg cursor-not-allowed inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-chevron-left text-xs"></i> <span>Prev</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-mono text-gray-300">Page <strong class="text-indigo-400"><?= $currentPage ?></strong> of <?= $totalPages ?> <span class="text-gray-500 hidden sm:inline">(<?= $totalItems ?> items)</span></span>
                                <div>
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="/?page=<?= ($currentPage + 1) ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition inline-flex items-center gap-1.5 shadow-sm">
                                            <span>Next</span> <i class="fa-solid fa-chevron-right text-xs"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="px-4 py-2 bg-gray-900/80 text-gray-600 rounded-lg cursor-not-allowed inline-flex items-center gap-1.5">
                                            <span>Next</span> <i class="fa-solid fa-chevron-right text-xs"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Diagnostic Manual Tag or Slug Lookup -->
                    <div class="mt-10 p-6 rounded-2xl bg-gray-950/50 border border-gray-800/80 text-left shadow-lg">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass-location text-indigo-400"></i>
                            <span>Manual Tag or Slug Lookup (Desktop &amp; Diagnostic Fallback)</span>
                        </span>
                        <form onsubmit="handleManualLookup(event)" class="flex flex-col sm:flex-row gap-3">
                            <input type="text" 
                                   id="manualTagInput" 
                                   placeholder="Enter Hardware UID (04:6A:F1:A2) or Slug (shelf-01)..." 
                                   required 
                                   class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-gray-200 font-mono text-sm focus:outline-none focus:border-indigo-500 shadow-inner transition" />
                            <button type="submit" class="px-6 py-3 rounded-xl bg-gray-800 hover:bg-gray-700 active:bg-gray-600 border border-gray-600 text-white text-sm font-semibold transition whitespace-nowrap shadow-sm flex items-center justify-center gap-2">
                                <i class="fa-solid fa-database text-indigo-400"></i> <span>Lookup Record</span>
                            </button>
                        </form>
                    </div>

                    <!-- System Footer with API Docs & GitHub Link -->
                    <footer class="mt-12 pt-6 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-400">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500 inline-block"></span>
                            <span class="font-mono text-gray-400">NFC Inventory &amp; State Tracker</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="/docs" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white font-semibold border border-gray-700 transition flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-book text-indigo-400"></i> <span>API Docs</span>
                            </a>
                            <a href="https://github.com/AzzieDev/NFC-Inventory" target="_blank" rel="noopener noreferrer" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white font-semibold border border-gray-700 transition flex items-center gap-2 shadow-sm">
                                <i class="fa-brands fa-github text-base text-gray-300"></i> <span>GitHub Repository</span>
                            </a>
                        </div>
                    </footer>

                </div>
            <?php elseif ($nativeHtml !== null): ?>
                <!-- Native Parsedown HTML Rendered Document -->
                <div class="max-w-4xl mx-auto p-5 sm:p-12 text-gray-200">
                    <div class="prose max-w-none pb-12">
                        <?= $nativeHtml ?>
                    </div>
                    <?php if ($isAdmin && $activeSlug): ?>
                        <?php $encodedSlug = str_replace('%2F', '/', rawurlencode($activeSlug)); ?>
                        <div class="mt-8 pt-6 border-t border-gray-800 flex flex-wrap items-center justify-between gap-4">
                            <a href="/admin/content/edit?item=<?= $encodedSlug ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition shadow flex items-center gap-2">
                                <i class="fa-solid fa-pen-to-square"></i> <span>Edit Document in Desktop Editor</span>
                            </a>
                            <a href="/content/<?= $encodedSlug ?>/raw" target="_blank" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-mono rounded-lg border border-gray-700 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-code"></i> <span>View Gist-Style Raw Text</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- IFRAME WORKSPACE FOR EXTERNAL OR REMOTE WEBPAGES -->
            <iframe id="contentFrame" 
                    src="<?= htmlspecialchars($initialUrl, ENT_QUOTES, 'UTF-8') ?>" 
                    class="w-full h-full border-0 block transition-all duration-300"
                    allow="geolocation *; microphone *; camera *; midi *; encrypted-media *; autoplay *">
            </iframe>
        <?php endif; ?>
    </main>

    <script>
        const input = document.getElementById('urlInput');
        const frame = document.getElementById('contentFrame');
        const isNative = <?= $isNativeView ? 'true' : 'false' ?>;
        
        let isInverted = !isNative;

        function applyInversionState() {
            const btn = document.getElementById('darkToggleBtn');
            const targetEl = isNative ? document.getElementById('mainWorkspace') : frame;
            
            if (isInverted && targetEl) {
                targetEl.style.filter = 'invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%)';
                btn.classList.add('!bg-indigo-900', '!border-indigo-500', 'text-white');
            } else if (targetEl) {
                targetEl.style.filter = 'none';
                btn.classList.remove('!bg-indigo-900', '!border-indigo-500', 'text-white');
            }
        }

        applyInversionState();

        function toggleInvertFilter() {
            isInverted = !isInverted;
            applyInversionState();
            showToast(isInverted ? 'Universal color inversion enabled' : 'Color inversion reset');
        }

        function syncFramedUrl(newUrl) {
            input.value = newUrl;
            const urlObj = new URL(window.location.href);
            if (newUrl === '/' || newUrl === '/content' || newUrl.startsWith('/content/')) {
                if (window.location.pathname === '/' || window.location.pathname.startsWith('/content')) {
                    window.history.replaceState({}, '', newUrl);
                    return;
                }
            }
            urlObj.searchParams.set('p', newUrl);
            window.history.replaceState({path: urlObj.toString()}, '', urlObj.toString());
        }

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const target = this.value.trim();
                if (target === '/' || target.startsWith('/content') || target === '/content') {
                    window.location.href = target;
                } else if (frame) {
                    frame.src = target;
                    syncFramedUrl(target);
                } else {
                    window.location.href = '/browse?p=' + encodeURIComponent(target);
                }
            }
        });

        if (frame) {
            frame.addEventListener('load', function() {
                try {
                    const currentHref = frame.contentWindow.location.href;
                    if (currentHref && currentHref !== 'about:blank') {
                        syncFramedUrl(currentHref);
                    }
                } catch (err) {
                    // Cross-origin iframe loaded; maintain displayed input address
                }
            });
        }

        function goHome() {
            window.location.href = '/';
        }

        function openEditor() {
            const currentVal = input.value || '/';
            if (currentVal.startsWith('/content/')) {
                const itemSlug = decodeURIComponent(currentVal.replace('/content/', '').split('#')[0].split('?')[0]);
                window.location.href = '/admin/content/edit?item=' + encodeURIComponent(itemSlug);
            } else {
                window.location.href = '/admin/content/edit';
            }
        }

        function handleManualLookup(e) {
            e.preventDefault();
            const val = document.getElementById('manualTagInput').value.trim();
            if (val) {
                window.location.href = '/' + encodeURIComponent(val);
            }
        }

        async function startInPageTagScan() {
            const dot = document.getElementById('nfcSensorDot');
            const text = document.getElementById('nfcSensorText');
            const feedback = document.getElementById('inPageScanFeedback');
            const btn = document.getElementById('inPageScanBtn');

            if (!('NDEFReader' in window)) {
                alert('Web NFC hardware scanning requires Chrome or Edge on an Android device. On desktop, use the manual tag lookup box below.');
                return;
            }

            try {
                const ndef = new NDEFReader();
                await ndef.scan();

                if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse';
                if (text) text.textContent = 'Listening for Chip Tap...';
                if (feedback) {
                    feedback.classList.remove('hidden');
                    feedback.textContent = 'Sensor armed! Touch your phone to the physical NFC chip now...';
                }
                if (btn) {
                    btn.classList.add('animate-pulse-ring');
                    btn.innerHTML = '<i class="fa-solid fa-satellite-dish animate-pulse"></i> <span>Touch Chip...</span>';
                }

                ndef.addEventListener('reading', ({ serialNumber }) => {
                    if (feedback) feedback.textContent = 'Tag detected (' + serialNumber + '). Resolving route...';
                    if (btn) btn.classList.remove('animate-pulse-ring');
                    setTimeout(() => {
                        window.location.href = '/' + encodeURIComponent(serialNumber);
                    }, 400);
                });

                ndef.addEventListener('error', () => {
                    if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-red-500';
                    if (text) text.textContent = 'Hardware Read Error';
                    if (feedback) feedback.textContent = 'Failed to communicate with NFC chip. Try tapping again.';
                });

            } catch (err) {
                alert('NFC sensor activation error: ' + err.message);
            }
        }

        async function fastAssignTag() {
            if (!('NDEFReader' in window)) {
                alert('Web NFC hardware sensing is not available in this browser. Please access via Chrome or Edge on a supported Android device.');
                return;
            }

            const btn = document.getElementById('updateTagBtn');
            const originalHtml = '<i class="fa-solid fa-wifi"></i> <span class="hidden sm:inline">Update Tag</span>';
            
            try {
                const ndef = new NDEFReader();
                await ndef.scan();
                btn.innerHTML = '<i class="fa-solid fa-satellite-dish animate-pulse"></i> <span>Tap Chip...</span>';
                btn.classList.add('!bg-amber-600', '!border-amber-500');
                showToast('NFC sensor armed. Tap physical chip now...');

                ndef.onreading = async (event) => {
                    const serial = event.serialNumber || '';
                    if (!serial) {
                        showToast('Failed to read chip hardware UID.');
                        return;
                    }
                    
                    const activeUrl = (input.value && input.value.trim() !== '') ? input.value.trim() : window.location.pathname;
                    let targetBindUrl = activeUrl.startsWith('http://') || activeUrl.startsWith('https://') 
                        ? activeUrl 
                        : window.location.origin + (activeUrl.startsWith('/') ? '' : '/') + activeUrl;
                    if (!activeUrl.startsWith('http://') && !activeUrl.startsWith('https://') && activeUrl !== '/' && !activeUrl.startsWith('/content')) {
                        targetBindUrl = window.location.origin + '/browse?p=' + encodeURIComponent(activeUrl);
                    }

                    let cleanName = 'Index Catalog';
                    if (activeUrl.startsWith('/content/')) {
                        const rawSlug = activeUrl.replace('/content/', '').split('#')[0].split('?')[0];
                        cleanName = decodeURIComponent(rawSlug).replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    } else if (activeUrl !== '/') {
                        cleanName = activeUrl.split('/').pop() || 'Web Resource';
                    }

                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving...</span>';
                    
                    async function sendFastBind(forceDuplicate = false) {
                        const resp = await fetch('/admin/api/fast-bind', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                uid: serial,
                                target_url: targetBindUrl,
                                friendly_name: cleanName,
                                force_duplicate: forceDuplicate
                            })
                        });
                        return await resp.json();
                    }

                    try {
                        let data = await sendFastBind(false);
                        if (data.status === 'warn_duplicate') {
                            const confirmed = await showDuplicateDialog(data.existing_tags || []);
                            if (!confirmed) {
                                showToast('Tag assignment cancelled.');
                                btn.innerHTML = originalHtml;
                                btn.classList.remove('!bg-amber-600', '!border-amber-500', '!bg-emerald-600', '!border-emerald-500');
                                return;
                            }
                            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving...</span>';
                            data = await sendFastBind(true);
                        }
                        
                        if (data.status === 'requires_form' && data.redirect_url) {
                            showToast('Untracked chip detected. Redirecting to specify custom slug...');
                            btn.innerHTML = '<i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Redirecting...</span>';
                            window.location.href = data.redirect_url;
                            return;
                        } else if (data.status === 'success') {
                            showToast('Tag (' + serial + ') instantly assigned to current page!');
                            btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Bound!</span>';
                            btn.classList.remove('!bg-amber-600', '!border-amber-500');
                            btn.classList.add('!bg-emerald-600', '!border-emerald-500');
                        } else {
                            showToast('Error: ' + (data.message || 'Binding failed.'));
                            btn.innerHTML = originalHtml;
                            btn.classList.remove('!bg-amber-600', '!border-amber-500', '!bg-emerald-600', '!border-emerald-500');
                        }
                    } catch (netErr) {
                        showToast('Network error during tag assignment.');
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('!bg-amber-600', '!border-amber-500', '!bg-emerald-600', '!border-emerald-500');
                    }
                    
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('!bg-amber-600', '!border-amber-500', '!bg-emerald-600', '!border-emerald-500');
                    }, 3000);
                };

            } catch (error) {
                alert('NFC scanning error: ' + error.message);
                btn.innerHTML = originalHtml;
                btn.classList.remove('!bg-amber-600', '!border-amber-500', '!bg-emerald-600', '!border-emerald-500');
            }
        }

        function showToast(message) {
            const existing = document.getElementById('nfc-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'nfc-toast';
            toast.className = 'fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-gray-900 border border-gray-700 text-gray-100 px-5 py-2.5 rounded-full shadow-2xl text-xs sm:text-sm font-semibold z-50 transition-opacity duration-300 pointer-events-none border-l-4 border-l-indigo-500';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
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
                    el.className = 'flex items-center justify-between py-1.5 border-b border-slate-800/50 last:border-0';
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
    </script>

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
                The destination URI for this page is already linked to one or more existing chips in your inventory. Do you still wish to bind this hardware chip to the exact same URI?
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
</body>
</html>
