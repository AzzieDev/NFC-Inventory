<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Interactive Desktop-Friendly Markdown Editor & File Manager (/admin/content/edit)
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

$slug = $slug ?? '';
$markdown = $markdown ?? '';
$fileList = $fileList ?? [];
$searchQuery = $searchQuery ?? '';
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$totalItems = $totalItems ?? 0;
?>
<!DOCTYPE html>
<html lang="en" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Markdown Data Service Editor — NFC Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0f1117; color: #e2e8f0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .prose h1 { color: #f8fafc; font-size: 1.75rem; font-weight: 700; margin-bottom: 0.75rem; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; }
        .prose h2 { color: #f1f5f9; font-size: 1.4rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .prose h3 { color: #e2e8f0; font-size: 1.2rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .prose p { margin-bottom: 1rem; line-height: 1.6; color: #cbd5e1; }
        .prose ul, .prose ol { margin-left: 1.5rem; margin-bottom: 1rem; list-style-type: disc; color: #cbd5e1; }
        .prose li { margin-bottom: 0.35rem; }
        .prose a { color: #818cf8; text-decoration: underline; }
        .prose code { background: #1e293b; color: #f8fafc; padding: 0.2rem 0.4rem; border-radius: 0.375rem; font-family: monospace; font-size: 0.9em; }
        .prose pre { background: #0b0d14; padding: 1rem; border-radius: 0.5rem; border: 1px solid #334155; overflow-x: auto; margin-bottom: 1.25rem; }
        .prose table { width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 1.25rem; }
        .prose th, .prose td { border: 1px solid #334155; padding: 0.6rem; }
        .prose th { background: #1e293b; color: #f8fafc; font-weight: 600; }
    </style>
</head>
<body class="min-h-screen flex flex-col p-4 md:p-6 max-w-[1600px] mx-auto w-full">
    <!-- Header Controls -->
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-800 pb-5 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-indigo-950/80 border border-indigo-500/40 rounded-xl flex items-center justify-center text-indigo-400 text-xl shadow-lg shrink-0">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider block">Data Service Editor</span>
                <h1 class="text-xl sm:text-2xl font-bold text-white">Markdown Data Management &amp; Editor</h1>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <a href="/" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs sm:text-sm transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-globe"></i> <span>View Browser Service</span>
            </a>
            <a href="/admin" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white font-semibold rounded-lg text-xs sm:text-sm border border-gray-700 transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-table-cells-large"></i> <span>Dashboard</span>
            </a>
        </div>
    </header>

    <!-- Desktop Workspace Grid: Left File Management Sidebar vs. Right Editor Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 flex-grow items-start">
        <!-- Sidebar: File Management & Drag/Drop Upload Zone -->
        <div id="fileManagerSidebar" class="lg:col-span-1 bg-gray-900 border border-gray-800 rounded-xl p-4 shadow-xl transition-all duration-200 flex flex-col">
            <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-gray-800">
                <h2 class="text-sm font-bold text-gray-200 uppercase tracking-wider flex items-center gap-2 truncate">
                    <i class="fa-solid fa-folder-tree text-indigo-400"></i> <span class="truncate">Storage Management</span>
                </h2>
                <span class="text-xs text-gray-400 font-mono shrink-0"><?= $totalItems ?> files</span>
            </div>

            <div class="flex items-center gap-2 mb-3">
                <button onclick="createNewItem()" title="Create New Document" class="flex-1 py-2 px-3 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold rounded-lg text-xs transition shadow flex items-center justify-center gap-1.5 min-w-0">
                    <i class="fa-solid fa-plus shrink-0"></i> <span class="truncate">New Doc</span>
                </button>
                <label for="fileUploadInput" title="Upload Markdown files directly into storage" class="py-2 px-3 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 text-gray-200 hover:text-white font-bold rounded-lg text-xs transition shadow flex items-center justify-center gap-1.5 cursor-pointer border border-gray-700 shrink-0">
                    <i class="fa-solid fa-file-arrow-up text-indigo-400"></i> <span>Upload</span>
                    <input type="file" id="fileUploadInput" accept=".md,.txt,text/*" multiple class="hidden" onchange="handleFileInputChange(event)" />
                </label>
            </div>

            <!-- Sidebar Search Filter & Clear Button -->
            <form action="/admin/content/edit" method="get" class="mb-3 flex items-center gap-1.5">
                <?php if ($slug !== ''): ?>
                    <input type="hidden" name="item" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" />
                <?php endif; ?>
                <div class="relative flex-grow min-w-0">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                    <input type="text" 
                           name="q" 
                           value="<?= htmlspecialchars($searchQuery ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Search storage..." 
                           class="w-full pl-8 pr-2.5 py-1.5 bg-gray-950 border border-gray-700 rounded-lg text-xs text-gray-200 focus:outline-none focus:border-indigo-500 shadow-inner truncate font-mono" />
                </div>
                <button type="submit" class="px-2.5 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-200 font-bold rounded-lg text-xs border border-gray-700 transition shrink-0" title="Apply Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="/admin/content/edit<?= $slug !== '' ? '?item=' . urlencode($slug) : '' ?>" title="Clear Filter" class="px-2 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-xs border border-gray-700 transition shrink-0">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>
            
            <p class="text-[11px] text-gray-400 text-center pb-2.5 border-b border-gray-800/60 mb-2 font-medium">
                <i class="fa-solid fa-cloud-arrow-up mr-1 text-indigo-400"></i> Drag &amp; drop files here to upload instantly
            </p>

            <!-- File List sorted by date-reversed order (active file pinned to top) -->
            <div class="space-y-1 max-h-[55vh] overflow-y-auto pr-1 flex-grow">
                <?php if (empty($fileList)): ?>
                    <p class="text-xs text-gray-500 italic py-6 text-center">No matching markdown files found.</p>
                <?php else: ?>
                    <?php foreach ($fileList as $idx => $item): ?>
                        <?php 
                        $isSelected = (strcasecmp((string)$item['slug'], (string)$slug) === 0); 
                        $encodedSlug = str_replace('%2F', '/', rawurlencode($item['slug']));
                        ?>
                        <div class="flex items-center justify-between px-2.5 py-2 rounded-lg border transition <?= $isSelected ? 'bg-indigo-950/90 border-indigo-500 text-white font-bold shadow-md' : 'bg-gray-950/50 border-gray-800/80 text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                            <a href="/admin/content/edit?item=<?= $encodedSlug ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="flex items-center gap-2.5 flex-grow min-w-0 mr-2">
                                <i class="fa-regular <?= $isSelected ? 'fa-file-lines text-indigo-400 font-bold' : 'fa-file text-gray-500' ?> shrink-0 text-xs"></i>
                                <div class="min-w-0 flex-1">
                                    <span class="block text-xs truncate text-left <?= $isSelected ? 'text-indigo-200' : '' ?>"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="block text-[10px] <?= $isSelected ? 'text-indigo-400/80' : 'text-gray-500' ?> font-mono truncate text-left">/<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </a>
                            <button onclick="deleteItem('<?= htmlspecialchars(addslashes($item['slug']), ENT_QUOTES, 'UTF-8') ?>')" title="Delete File from Disk" class="text-gray-500 hover:text-red-400 p-1.5 rounded transition shrink-0">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 10-Item Sidebar Pagination Controls -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-3 pt-3 border-t border-gray-800 flex items-center justify-between text-xs text-gray-400 font-mono">
                    <div>
                        <?php if ($currentPage > 1): ?>
                            <a href="/admin/content/edit?page=<?= ($currentPage - 1) ?><?= $slug !== '' ? '&item=' . urlencode($slug) : '' ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 text-white rounded transition inline-flex items-center gap-1">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i> <span>Prev</span>
                            </a>
                        <?php else: ?>
                            <span class="px-2.5 py-1 bg-gray-950 text-gray-600 rounded cursor-not-allowed inline-flex items-center gap-1">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i> <span>Prev</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <span><?= $currentPage ?> / <?= $totalPages ?></span>
                    <div>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="/admin/content/edit?page=<?= ($currentPage + 1) ?><?= $slug !== '' ? '&item=' . urlencode($slug) : '' ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 text-white rounded transition inline-flex items-center gap-1">
                                <span>Next</span> <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-2.5 py-1 bg-gray-950 text-gray-600 rounded cursor-not-allowed inline-flex items-center gap-1">
                                <span>Next</span> <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Main Editor Workspace -->
        <div class="lg:col-span-3 bg-gray-900 border border-gray-800 rounded-xl p-5 shadow-xl flex flex-col min-h-[600px]">
            <!-- Top Action Toolbar & Raw Gist Link -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 mb-5 pb-4 border-b border-gray-800">
                <div class="flex-grow flex flex-wrap sm:flex-nowrap items-center gap-2">
                    <div class="relative flex-grow min-w-[220px]">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs sm:text-sm font-mono font-bold">/content/</span>
                        <input id="slugInput" 
                               type="text" 
                               value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" 
                               placeholder="e.g. Storage Bin 01 - Top Shelf" 
                               class="w-full bg-gray-950 border border-gray-700 rounded-lg pl-20 sm:pl-24 pr-12 py-2 text-sm sm:text-base text-white font-mono focus:outline-none focus:border-indigo-500 shadow-inner" />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-mono font-bold">.md</span>
                    </div>
                    
                    <!-- Gist-Style Raw MD Plain-Text Link Bar -->
                    <div class="flex items-center gap-1 shrink-0">
                        <button id="copyRawBtn" onclick="copyRawLink()" <?= $slug === '' ? 'disabled' : '' ?> title="Copy Canonical Gist-Style Raw MD Link" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 disabled:opacity-40 disabled:pointer-events-none text-indigo-300 rounded-lg border border-gray-700 text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition">
                            <i class="fa-solid fa-link"></i> <span>Copy Raw Link</span>
                        </button>
                        <?php $encodedSlug = str_replace('%2F', '/', rawurlencode($slug)); ?>
                        <a id="viewRawBtn" href="/content/<?= $encodedSlug ?>/raw" target="_blank" <?= $slug === '' ? 'style="pointer-events:none; opacity:0.4;"' : '' ?> title="Open Raw Plain Text in New Tab" class="px-2.5 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg border border-gray-700 text-xs sm:text-sm transition">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0 justify-end">
                    <button id="previewToggleBtn" onclick="togglePreview()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 text-gray-200 font-semibold rounded-lg text-xs sm:text-sm border border-gray-700 transition flex items-center gap-2">
                        <i class="fa-solid fa-eye text-indigo-400"></i> <span>Live Preview</span>
                    </button>
                    <button onclick="saveItem()" class="px-6 py-2 bg-gradient-to-r from-emerald-600 to-teal-500 hover:brightness-110 text-white font-bold rounded-lg text-xs sm:text-sm shadow-lg transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> <span>Save Document</span>
                    </button>
                </div>
            </div>

            <!-- Editor Textarea / Live Preview Container -->
            <div class="flex-grow relative min-h-[550px]">
                <textarea id="markdownInput" 
                          placeholder="# Document Title&#10;&#10;Write or paste your markdown text here. Table formatting, code blocks, syntax, and lists are natively rendered via Parsedown..." 
                          class="w-full h-[65vh] min-h-[550px] bg-gray-950 border border-gray-800 rounded-xl p-5 font-mono text-sm sm:text-base text-gray-200 focus:outline-none focus:border-indigo-500 leading-relaxed shadow-inner block transition-all"><?= htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8') ?></textarea>
                
                <div id="previewDiv" class="hidden w-full h-[65vh] min-h-[550px] bg-gray-950 border border-gray-800 rounded-xl p-6 sm:p-10 prose prose-invert max-w-none overflow-y-auto shadow-inner">
                </div>
            </div>
        </div>
    </div>

    <script>
        const slugInput = document.getElementById('slugInput');
        const mdInput = document.getElementById('markdownInput');
        const previewDiv = document.getElementById('previewDiv');
        const previewBtn = document.getElementById('previewToggleBtn');
        let isPreviewOpen = false;
        
        // Track original slug so editing the name automatically renames the physical file instead of creating duplicates
        let originalSlug = "<?= htmlspecialchars(addslashes($slug), ENT_QUOTES, 'UTF-8') ?>";

        function createNewItem() {
            slugInput.value = '';
            originalSlug = '';
            mdInput.value = '';
            slugInput.focus();
            if (isPreviewOpen) togglePreview();
            updateRawButtons('');
            showToast('Ready to create new document. Specify slug and click Save Document.');
        }

        function updateRawButtons(slug) {
            const copyBtn = document.getElementById('copyRawBtn');
            const viewBtn = document.getElementById('viewRawBtn');
            if (!slug) {
                copyBtn.disabled = true;
                viewBtn.style.opacity = '0.4';
                viewBtn.style.pointerEvents = 'none';
            } else {
                copyBtn.disabled = false;
                viewBtn.style.opacity = '1';
                viewBtn.style.pointerEvents = 'auto';
                viewBtn.href = '/content/' + encodeURIComponent(slug).replace(/%2F/g, '/') + '/raw';
            }
        }

        slugInput.addEventListener('input', function() {
            updateRawButtons(this.value.trim());
        });

        function copyRawLink() {
            const slug = slugInput.value.trim();
            if (!slug) return;
            const canonicalUrl = window.location.origin + '/content/' + encodeURIComponent(slug).replace(/%2F/g, '/') + '/raw';
            navigator.clipboard.writeText(canonicalUrl).then(() => {
                showToast('Copied raw gist link to clipboard!');
            }).catch(() => {
                prompt('Copy raw markdown Gist link:', canonicalUrl);
            });
        }

        async function togglePreview() {
            isPreviewOpen = !isPreviewOpen;
            if (isPreviewOpen) {
                mdInput.classList.add('hidden');
                previewDiv.classList.remove('hidden');
                previewBtn.innerHTML = '<i class="fa-solid fa-code text-indigo-400"></i> <span>Edit Markdown</span>';
                previewBtn.classList.add('bg-indigo-950/80', 'border-indigo-500');

                previewDiv.innerHTML = '<div class="py-12 text-center text-gray-500"><i class="fa-solid fa-spinner fa-spin text-3xl mb-2 block"></i> Rendering Parsedown HTML...</div>';

                try {
                    const resp = await fetch('/api/v1/markdown/preview', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ markdown: mdInput.value })
                    });
                    const data = await resp.json();
                    if (data.status === 'success') {
                        previewDiv.innerHTML = data.html || '<p class="text-gray-500 italic">Empty document.</p>';
                    } else {
                        previewDiv.innerHTML = '<p class="text-red-400">Error rendering preview: ' + data.message + '</p>';
                    }
                } catch (err) {
                    previewDiv.innerHTML = '<p class="text-red-400">Network error during preview rendering.</p>';
                }
            } else {
                previewDiv.classList.add('hidden');
                mdInput.classList.remove('hidden');
                previewBtn.innerHTML = '<i class="fa-solid fa-eye text-indigo-400"></i> <span>Live Preview</span>';
                previewBtn.classList.remove('bg-indigo-950/80', 'border-indigo-500');
                mdInput.focus();
            }
        }

        async function saveItem() {
            const slug = slugInput.value.trim();
            const content = mdInput.value;

            if (!slug) {
                showToast('Please enter a valid filename / slug.');
                slugInput.focus();
                return;
            }

            try {
                const resp = await fetch('/admin/api/content/save', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ 
                        slug: slug, 
                        original_slug: originalSlug,
                        markdown: content 
                    })
                });
                const data = await resp.json();
                if (data.status === 'success') {
                    showToast('Document Saved: /content/' + data.slug);
                    originalSlug = data.slug;
                    setTimeout(() => {
                        window.location.href = '/admin/content/edit?item=' + encodeURIComponent(data.slug).replace(/%2F/g, '/');
                    }, 800);
                } else {
                    alert('Error saving document: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                alert('Network error saving file: ' + err.message);
            }
        }

        async function deleteItem(slug) {
            if (!confirm('Are you certain you want to permanently delete "' + slug + '.md" from storage? This cannot be undone.')) {
                return;
            }

            try {
                const resp = await fetch('/admin/api/content/delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ slug: slug })
                });
                const data = await resp.json();
                if (data.status === 'success') {
                    showToast('File deleted successfully.');
                    setTimeout(() => {
                        window.location.href = '/admin/content/edit';
                    }, 600);
                } else {
                    alert('Error deleting item: ' + (data.message || 'Delete failed'));
                }
            } catch (err) {
                alert('Network error during file deletion.');
            }
        }

        // Native Dependency-Free Drag and Drop File Storage Management
        const dropZone = document.getElementById('fileManagerSidebar');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('!border-indigo-500', '!bg-indigo-950/40');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('!border-indigo-500', '!bg-indigo-950/40');
            });
        });

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFileInputChange(e) {
            handleFiles(e.target.files);
        }

        async function handleFiles(files) {
            if (!files || files.length === 0) return;
            let uploadedCount = 0;
            showToast('Processing ' + files.length + ' file(s)...');

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!file.name.endsWith('.md') && !file.name.endsWith('.txt') && !file.type.startsWith('text/')) {
                    alert('Skipping unsupported file: ' + file.name + '. Please provide .md or text files.');
                    continue;
                }
                const content = await file.text();
                let slug = file.name.replace(/\.(md|txt)$/i, '');
                
                try {
                    const resp = await fetch('/admin/api/content/save', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ slug: slug, markdown: content })
                    });
                    const data = await resp.json();
                    if (data.status === 'success') {
                        uploadedCount++;
                    } else {
                        alert('Failed to save ' + file.name + ': ' + (data.message || 'Error'));
                    }
                } catch (err) {
                    alert('Network error uploading ' + file.name);
                }
            }

            if (uploadedCount > 0) {
                showToast('Stored ' + uploadedCount + ' file(s) into management! Refreshing...');
                setTimeout(() => {
                    window.location.reload();
                }, 800);
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
