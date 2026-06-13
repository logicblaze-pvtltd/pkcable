(function (global) {
    const stateStore = new WeakMap();

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[character];
        });
    }

    function getState(button) {
        if (!stateStore.has(button)) {
            stateStore.set(button, {
                html: button.innerHTML,
                text: button.textContent.trim(),
                disabled: button.disabled,
                ariaBusy: button.getAttribute('aria-busy'),
            });
        }

        return stateStore.get(button);
    }

    function renderLoadingMarkup(label) {
        return `
            <span class="inline-flex items-center justify-center gap-2">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>${escapeHtml(label)}</span>
            </span>
        `;
    }

    function setButtonLoading(button, isLoading, options = {}) {
        if (!button) return;

        const state = getState(button);

        if (isLoading) {
            const label = options.label || button.dataset.loadingText || state.text || 'Loading...';
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('opacity-70', 'cursor-not-allowed');
            button.innerHTML = renderLoadingMarkup(label);
            return;
        }

        button.disabled = state.disabled;

        if (state.ariaBusy === null) {
            button.removeAttribute('aria-busy');
        } else {
            button.setAttribute('aria-busy', state.ariaBusy);
        }

        button.classList.remove('opacity-70', 'cursor-not-allowed');
        button.innerHTML = state.html;
        stateStore.delete(button);
    }

    async function withButtonLoading(button, action, options = {}) {
        if (!button || typeof action !== 'function') {
            return action?.();
        }

        setButtonLoading(button, true, options);

        try {
            return await action();
        } finally {
            setButtonLoading(button, false);
        }
    }

    global.AppButtonLoading = {
        setButtonLoading,
        withButtonLoading,
    };
})(window);
