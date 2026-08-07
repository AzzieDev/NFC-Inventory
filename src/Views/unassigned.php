<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Tag Unassigned UI Template
 *
 * Variables passed from controller:
 * @var string $rawUid
 * @var string $normalizedUid
 * @var string $displayUid
 * @var bool $isHardwareSerial
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    exit('Direct view access not allowed.');
}

$rawUid = htmlspecialchars($rawUid ?? 'Unknown', ENT_QUOTES, 'UTF-8');
$normalizedUid = htmlspecialchars($normalizedUid ?? '', ENT_QUOTES, 'UTF-8');
$displayUid = htmlspecialchars($displayUid ?? $rawUid, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFC Tag Unassigned — NFC Inventory System</title>
    <!-- Modern Google Fonts: Outfit & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
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
                    colors: {
                        primary: {
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        },
                        cyan: {
                            400: '#00f2fe',
                            500: '#00c6ff',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0b0d14;
            background-image: 
                radial-gradient(at 15% 20%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 85% 80%, rgba(0, 242, 254, 0.15) 0px, transparent 50%);
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(18, 22, 33, 0.78);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
        }
        .gradient-text {
            background: linear-gradient(135deg, #818cf8 0%, #00f2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="text-slate-100 font-sans antialiased p-4 md:p-8 flex flex-col items-center justify-center min-h-screen">
    <main class="max-w-xl w-full mx-auto glass-panel rounded-3xl p-6 md:p-10 border border-slate-700/50 space-y-6 text-center">
        
        <!-- Status Icon -->
        <div class="inline-flex p-4 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 text-indigo-400 shadow-inner">
            <svg class="w-12 h-12 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
        </div>

        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold uppercase tracking-wider">
                Physical-to-Digital Routing Engine
            </div>
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                Tag <span class="gradient-text">Unassigned</span>
            </h1>
            <p class="text-slate-400 text-sm md:text-base">
                This NFC chip has been successfully detected by the system, but is not currently linked to an active inventory item or record.
            </p>
        </div>

        <!-- Hardware Chip details diagnostic box -->
        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-700/80 text-left space-y-3 shadow-inner">
            <div class="flex items-center justify-between text-xs border-b border-slate-800 pb-2">
                <span class="font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Tag Identification &amp; Routing Details
                </span>
                <span class="text-slate-500 font-mono"><?= $isHardwareSerial ? 'Factory NDEF UID' : 'Custom Friendly Slug' ?></span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block mb-0.5">Scanned Input / Display Name</span>
                    <code class="text-cyan-400 font-mono font-bold text-base"><?= $displayUid ?></code>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block mb-0.5">Normalized DB Index / Slug</span>
                    <code class="text-emerald-400 font-mono font-bold text-base"><?= $normalizedUid ?></code>
                </div>
            </div>
        </div>

        <!-- Admin instructions / CTA -->
        <div class="pt-2 space-y-3">
            <p class="text-xs text-slate-400">
                Are you an authenticated administrator ready to associate this tag?
            </p>
            <a href="<?= htmlspecialchars(($prefix ?? '') . '/admin/inventory/bind?uid=' . rawurlencode($tagRecord['uid'] ?? $normalizedUid), ENT_QUOTES) ?>" class="block w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-primary-600 to-cyan-500 text-white font-bold text-base shadow-lg hover:brightness-110 active:scale-95 transition duration-200">
                Assign to Inventory in Admin Console
            </a>
            <a href="./" class="inline-block text-xs text-slate-500 hover:text-slate-300 underline transition duration-150 pt-2">
                &larr; Return to Catalog Home
            </a>
        </div>
        
    </main>
</body>
</html>
