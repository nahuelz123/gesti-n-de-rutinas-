(() => {
    const pending = new Map();

    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!form.matches('[data-vf-login]')) return;
        event.preventDefault();
        if (pending.has(form)) return;
        const controller = new AbortController();
        pending.set(form, controller);
        const timeout = setTimeout(() => controller.abort(), 15000);

        const error = form.querySelector('[data-login-session-error]');
        const buttons = form.querySelectorAll('[type="submit"]');
        error.hidden = true;
        buttons.forEach(button => button.disabled = true);

        try {
            // Forms restored by browser/Livewire history may contain an old CSRF token.
            const response = await fetch(form.dataset.sessionUrl, {
                credentials: 'same-origin', cache: 'no-store',
                signal: controller.signal,
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            });
            if (!response.ok) throw new Error('session');
            const data = await response.json();
            if (controller.signal.aborted || !form.isConnected) return;
            if (typeof data.token !== 'string' || !data.token) throw new Error('token');
            form.querySelector('[name="_token"]').value = data.token;
            HTMLFormElement.prototype.submit.call(form);
        } catch (_) {
            if (pending.get(form) !== controller) return;
            pending.delete(form);
            buttons.forEach(button => button.disabled = false);
            error.textContent = 'No pudimos actualizar la sesión. Revisá tu conexión y volvé a intentar.';
            error.hidden = false;
        } finally {
            clearTimeout(timeout);
        }
    });

    function resetForms() {
        pending.forEach(controller => controller.abort());
        pending.clear();
        document.querySelectorAll('[data-vf-login]').forEach(form => {
            form.querySelectorAll('[type="submit"]').forEach(button => button.disabled = false);
        });
    }

    document.addEventListener('livewire:navigating', resetForms);
    document.addEventListener('livewire:navigated', resetForms);
    window.addEventListener('pageshow', event => {
        if (event.persisted) resetForms();
    });
})();
