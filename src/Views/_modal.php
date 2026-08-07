<!-- Custom Global Modal UI Component -->
<div id="appModalOverlay" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 backdrop-blur-sm bg-slate-950/70 transition-opacity opacity-0">
    <div id="appModalBox" class="bg-slate-900 border border-slate-700/80 rounded-2xl p-6 md:p-8 max-w-sm w-full shadow-2xl transform scale-95 transition-all">
        <div class="flex items-center gap-3 mb-4">
            <div id="appModalIcon" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-indigo-500/20 text-indigo-400">
                <i class="fa-solid fa-bell text-lg"></i>
            </div>
            <h3 id="appModalTitle" class="text-lg font-bold text-white tracking-wide">Notice</h3>
        </div>
        
        <p id="appModalMessage" class="text-slate-300 text-sm mb-5 leading-relaxed"></p>
        
        <!-- Optional Prompt Input -->
        <div id="appModalInputWrapper" class="hidden mb-5">
            <input type="text" id="appModalInput" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:border-indigo-500 focus:outline-none transition" />
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 mt-2">
            <button id="appModalBtnCancel" class="hidden px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">Cancel</button>
            <button id="appModalBtnConfirm" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition shadow-lg">OK</button>
        </div>
    </div>
</div>

<script>
    /**
     * Unified Custom Modal System
     */
    const _appModal = {
        overlay: document.getElementById('appModalOverlay'),
        box: document.getElementById('appModalBox'),
        title: document.getElementById('appModalTitle'),
        message: document.getElementById('appModalMessage'),
        icon: document.getElementById('appModalIcon'),
        inputWrapper: document.getElementById('appModalInputWrapper'),
        input: document.getElementById('appModalInput'),
        btnCancel: document.getElementById('appModalBtnCancel'),
        btnConfirm: document.getElementById('appModalBtnConfirm'),
        resolvePromise: null,

        show: function(type, msg, defaultVal, titleOverride) {
            return new Promise((resolve) => {
                this.resolvePromise = resolve;
                this.message.innerHTML = msg.replace(/\n/g, '<br>');
                
                // Reset styles
                this.inputWrapper.classList.add('hidden');
                this.btnCancel.classList.add('hidden');
                this.input.value = '';
                
                // Setup configurations based on type
                if (type === 'alert') {
                    this.title.textContent = titleOverride || 'Alert';
                    this.icon.innerHTML = '<i class="fa-solid fa-circle-exclamation text-lg"></i>';
                    this.icon.className = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-amber-500/20 text-amber-400';
                    this.btnConfirm.textContent = 'OK';
                    this.btnConfirm.className = 'px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition shadow-lg';
                } else if (type === 'confirm') {
                    this.title.textContent = titleOverride || 'Confirm Action';
                    this.icon.innerHTML = '<i class="fa-solid fa-circle-question text-lg"></i>';
                    this.icon.className = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-blue-500/20 text-blue-400';
                    this.btnCancel.classList.remove('hidden');
                    this.btnConfirm.textContent = 'Confirm';
                    this.btnConfirm.className = 'px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition shadow-lg';
                } else if (type === 'prompt') {
                    this.title.textContent = titleOverride || 'Input Required';
                    this.icon.innerHTML = '<i class="fa-solid fa-keyboard text-lg"></i>';
                    this.icon.className = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-emerald-500/20 text-emerald-400';
                    this.btnCancel.classList.remove('hidden');
                    this.inputWrapper.classList.remove('hidden');
                    this.input.value = defaultVal || '';
                    this.btnConfirm.textContent = 'Submit';
                    this.btnConfirm.className = 'px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition shadow-lg';
                }
                
                // Display logic
                this.overlay.classList.remove('hidden');
                this.overlay.classList.add('flex');
                
                // Trigger animation
                requestAnimationFrame(() => {
                    this.overlay.classList.remove('opacity-0');
                    this.box.classList.remove('scale-95');
                    this.box.classList.add('scale-100');
                    if (type === 'prompt') {
                        this.input.focus();
                    }
                });
            });
        },

        close: function(result) {
            this.overlay.classList.add('opacity-0');
            this.box.classList.remove('scale-100');
            this.box.classList.add('scale-95');
            setTimeout(() => {
                this.overlay.classList.add('hidden');
                this.overlay.classList.remove('flex');
                if (this.resolvePromise) {
                    this.resolvePromise(result);
                    this.resolvePromise = null;
                }
            }, 200);
        }
    };

    // Bind event listeners
    _appModal.btnCancel.addEventListener('click', () => _appModal.close(false));
    _appModal.btnConfirm.addEventListener('click', () => {
        if (!_appModal.inputWrapper.classList.contains('hidden')) {
            _appModal.close(_appModal.input.value);
        } else {
            _appModal.close(true);
        }
    });
    
    // Allow 'Enter' key on input for prompt
    _appModal.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            _appModal.close(_appModal.input.value);
        }
    });

    // Public asynchronous API for global usage
    window.appAlert = async function(msg, title) { return await _appModal.show('alert', msg, null, title); };
    window.appConfirm = async function(msg, title) { return await _appModal.show('confirm', msg, null, title); };
    window.appPrompt = async function(msg, defaultVal, title) { return await _appModal.show('prompt', msg, defaultVal, title); };
</script>
