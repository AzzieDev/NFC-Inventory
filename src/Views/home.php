<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Home Catalog & Interactive Web NFC Scanner Dashboard
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    exit('Direct access forbidden.');
}

$isBindMode = isset($_GET['mode']) && (string) $_GET['mode'] === 'bind';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFC Inventory &amp; State Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
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
                radial-gradient(at 10% 10%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 90% 90%, rgba(0, 242, 254, 0.15) 0px, transparent 50%);
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
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(99, 102, 241, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }
        .animate-pulse-ring {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>
<body class="text-slate-100 font-sans antialiased p-4 md:p-8 flex flex-col items-center justify-center min-h-screen">
    <main class="max-w-2xl w-full mx-auto glass-panel rounded-3xl p-6 md:p-10 border border-slate-700/50 space-y-8 text-center">
        
        <?php if ($isBindMode): ?>
            <!-- Administrator Bind & Edit Override Banner -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-500/15 to-indigo-500/15 border border-amber-500/30 text-left flex items-center justify-between gap-3 shadow-lg">
                <div class="flex items-start gap-3">
                    <span class="text-xl">🔧</span>
                    <div>
                        <span class="text-xs font-bold text-amber-400 uppercase tracking-wider block">Admin Tag Configuration Mode Active</span>
                        <p class="text-xs text-slate-300">Tapping a tag or looking up a UID will immediately open its admin configuration form rather than redirecting to its destination URL.</p>
                    </div>
                </div>
                <a href="./admin" class="text-xs font-semibold px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl border border-slate-600 transition whitespace-nowrap">&larr; Back to Admin</a>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                Physical-to-Digital Tracker
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">
                NFC <span class="gradient-text">Inventory</span>
            </h1>
            <p class="text-slate-400 text-sm md:text-base max-w-md mx-auto">
                <?= $isBindMode ? 'Touch a tag to assign/edit its properties, or manually enter an existing UID below.' : 'Tap a physical NFC tag against your mobile device or manually enter an identifier below to follow its assigned link.' ?>
            </p>
        </div>

        <!-- Interactive Web NFC Scanner Card -->
        <div id="scannerCard" class="p-6 rounded-2xl bg-slate-900/90 border border-slate-700/80 space-y-5 shadow-inner">
            <div class="flex items-center justify-between text-xs border-b border-slate-800 pb-3">
                <span class="font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <span id="nfcStatusDot" class="w-2.5 h-2.5 rounded-full <?= $isBindMode ? 'bg-amber-400 animate-pulse' : 'bg-slate-500' ?>"></span>
                    Web NFC Sensor Status
                </span>
                <span id="nfcStatusText" class="text-slate-500 font-mono"><?= $isBindMode ? 'Ready (Bind Mode)' : 'Standby' ?></span>
            </div>

            <div class="py-2">
                <button id="scanBtn" onclick="startTagScan()" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-primary-600 to-cyan-500 text-white font-bold text-lg shadow-lg hover:brightness-110 active:scale-95 transition duration-200 flex items-center justify-center gap-3 mx-auto">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                    </svg>
                    <?= $isBindMode ? 'Tap to Scan & Configure Tag' : 'Tap to Scan NFC Tag' ?>
                </button>
            </div>

            <p id="scanFeedback" class="text-xs text-slate-400 hidden animate-pulse">
                ⚡ NFC Sensor Armed! Hold your phone against the physical NFC tag...
            </p>
        </div>

        <!-- Manual Lookup Input Box -->
        <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-700/50 space-y-4 text-left">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">
                <?= $isBindMode ? 'Manual UID or Slug Lookup for Editing (Desktop / Fallback)' : 'Manual Tag or Slug Lookup (Desktop / Diagnostic)' ?>
            </span>
            <form onsubmit="handleManualLookup(event)" class="flex flex-col sm:flex-row gap-3">
                <input 
                    type="text" 
                    id="manualUid" 
                    placeholder="Enter Hardware UID (04:6A:F1:A2) or Slug (shelf-01)..." 
                    required
                    class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 font-mono text-sm focus:outline-none focus:border-cyan-500 transition duration-150"
                >
                <button type="submit" class="px-6 py-3 rounded-xl <?= $isBindMode ? 'bg-indigo-600 hover:bg-indigo-500 border-indigo-500 font-bold' : 'bg-slate-800 hover:bg-slate-700 border border-slate-600 font-semibold' ?> text-white text-sm transition duration-150 whitespace-nowrap">
                    <?= $isBindMode ? 'Launch Edit Form &rarr;' : 'Check DB Status &rarr;' ?>
                </button>
            </form>
        </div>

        <!-- Footer Documentation Quick Link -->
        <div class="pt-2 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <span>System State: <strong>Phase 1 Foundational Routing Active</strong></span>
            <div class="flex items-center gap-4">
                <a href="./admin" class="text-slate-400 hover:text-white font-semibold transition">Admin Panel</a>
                <a href="./docs" class="text-indigo-400 hover:text-indigo-300 underline font-semibold transition duration-150">
                    Interactive API Docs &rarr;
                </a>
            </div>
        </div>

    </main>

    <script>
        const isBindMode = <?= $isBindMode ? 'true' : 'false' ?>;

        // Web NFC Scan Logic for Chrome / Edge Android
        async function startTagScan() {
            const statusDot = document.getElementById('nfcStatusDot');
            const statusText = document.getElementById('nfcStatusText');
            const feedback = document.getElementById('scanFeedback');
            const btn = document.getElementById('scanBtn');

            if (!('NDEFReader' in window)) {
                alert('Web NFC scanning requires Edge or Chrome on an Android device. Please use the Manual Lookup box below if you are on desktop!');
                return;
            }

            try {
                const ndef = new NDEFReader();
                await ndef.scan();
                
                statusDot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse';
                statusText.className = 'text-emerald-400 font-mono font-bold';
                statusText.innerText = 'Listening for Tap...';
                feedback.classList.remove('hidden');
                btn.classList.add('animate-pulse-ring');
                btn.innerHTML = '⚡ Ready: Touch Tag to Phone...';

                ndef.addEventListener("reading", ({ serialNumber }) => {
                    if (isBindMode) {
                        feedback.innerHTML = '✅ Tag Scanned: <span class="text-cyan-400 font-mono">' + serialNumber + '</span>. Opening assignment form...';
                        btn.classList.remove('animate-pulse-ring');
                        setTimeout(() => {
                            window.location.href = './admin/inventory/bind?uid=' + encodeURIComponent(serialNumber);
                        }, 400);
                    } else {
                        feedback.innerHTML = '✅ Tag Scanned: <span class="text-cyan-400 font-mono">' + serialNumber + '</span>. Resolving link in database...';
                        btn.classList.remove('animate-pulse-ring');
                        setTimeout(() => {
                            window.location.href = './' + encodeURIComponent(serialNumber);
                        }, 600);
                    }
                });

                ndef.addEventListener("error", () => {
                    statusDot.className = 'w-2.5 h-2.5 rounded-full bg-red-500';
                    statusText.className = 'text-red-400 font-mono';
                    statusText.innerText = 'Read Error';
                    feedback.innerText = '❌ Failed to read NFC chip. Please tap again.';
                });

            } catch (error) {
                alert('NFC Sensor Error: ' + error.message);
            }
        }

        // Manual Navigation Handler
        function handleManualLookup(event) {
            event.preventDefault();
            const inputVal = document.getElementById('manualUid').value.trim();
            if (inputVal) {
                if (isBindMode) {
                    window.location.href = './admin/inventory/bind?uid=' + encodeURIComponent(inputVal);
                } else {
                    window.location.href = './' + encodeURIComponent(inputVal);
                }
            }
        }
    </script>
</body>
</html>
