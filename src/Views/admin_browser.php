<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Public Mobile-First Browser Shell (/browse & /content) with Optional Admin Controls and Hybrid Native/iFrame Rendering
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

$initialUrl = $initialUrl ?? trim((string) ($_GET['p'] ?? '/content'));
if ($initialUrl === '' || $initialUrl === '/markdown-blog' || $initialUrl === './markdown-blog' || $initialUrl === '/blog') {
    $initialUrl = '/content';
}

$isNativeView = ($contentIndex !== null || $nativeHtml !== null);
?>
<!DOCTYPE html>
<html lang="en" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>NFC Data Content & iFrame Browser</title>
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
    </style>
</head>
<body class="h-full flex flex-col overflow-hidden">
    <!-- Ultra-Primitive Uniform Touchscreen Header Toolbar -->
    <header class="h-14 bg-gray-900 border-b border-gray-800 px-2 sm:px-3 flex items-center gap-1 sm:gap-1.5 shrink-0 z-20 shadow-lg">
        <button onclick="goHome()" title="Home (/content)" class="h-9 w-9 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 rounded-lg text-gray-300 hover:text-white border border-gray-700 transition flex items-center justify-center shrink-0 shadow-sm">
            <i class="fa-solid fa-house"></i>
        </button>

        <input id="urlInput" 
               type="text" 
               value="<?= htmlspecialchars($initialUrl, ENT_QUOTES, 'UTF-8') ?>" 
               placeholder="/content" 
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

    <!-- Full-Screen Hybrid Workspace: Native DOM for internal items vs. iFrame for external websites -->
    <main id="mainWorkspace" class="flex-grow w-full h-[calc(100vh-3.5rem)] relative bg-[#0f1117] overflow-y-auto">
        <?php if ($isNativeView): ?>
            <!-- NATIVE PARSEDOWN CONTENT RENDERING (ZERO IFRAMES) -->
            <?php if ($contentIndex !== null): ?>
                <!-- Content Index Listing (Date-Reversed Order, left aligned, truncates to ellipsis with no > button) -->
                <div class="max-w-4xl mx-auto p-4 sm:p-10 text-gray-200">
                    <div class="flex items-center justify-between pb-5 border-b border-gray-800 mb-6">
                        <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2.5">
                            <i class="fa-solid fa-server text-indigo-500"></i>
                            <span>Data Serving Catalog</span>
                        </h1>
                        <?php if ($isAdmin): ?>
                            <a href="/admin/content/edit" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs sm:text-sm shadow transition flex items-center gap-1.5 shrink-0">
                                <i class="fa-solid fa-plus"></i> <span>New Item</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($contentIndex)): ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fa-solid fa-folder-open text-4xl mb-3 block opacity-50"></i>
                            <p class="text-base font-medium">No markdown files found in storage.</p>
                            <?php if ($isAdmin): ?>
                                <p class="text-sm mt-2"><a href="/admin/content/edit" class="text-indigo-400 hover:underline">Click here to create or upload markdown documents</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-800/80 border border-gray-800 rounded-xl bg-gray-950/60 overflow-hidden shadow-xl">
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
                    <?php endif; ?>
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
            if (newUrl === '/content' || newUrl.startsWith('/content/')) {
                if (window.location.pathname.startsWith('/content')) {
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
                if (target.startsWith('/content') || target === '/content') {
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
            window.location.href = '/content';
        }

        function openEditor() {
            const currentVal = input.value || '/content';
            if (currentVal.startsWith('/content/')) {
                const itemSlug = decodeURIComponent(currentVal.replace('/content/', '').split('#')[0].split('?')[0]);
                window.location.href = '/admin/content/edit?item=' + encodeURIComponent(itemSlug);
            } else {
                window.location.href = '/admin/content/edit';
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
                    
                    const activeUrl = input.value || '/content';
                    let targetBindUrl = window.location.origin + activeUrl;
                    if (!activeUrl.startsWith('/content')) {
                        targetBindUrl = window.location.origin + '/browse?p=' + encodeURIComponent(activeUrl);
                    }

                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving...</span>';
                    
                    try {
                        const resp = await fetch('/admin/api/fast-bind', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                uid: serial,
                                target_url: targetBindUrl,
                                friendly_name: 'Framed Content (' + (activeUrl.split('/').pop() || 'index') + ')'
                            })
                        });
                        
                        const data = await resp.json();
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
    </script>
</body>
</html>
