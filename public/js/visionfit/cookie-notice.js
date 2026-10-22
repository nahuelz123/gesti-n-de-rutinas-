(() => {
    const key = 'vf-cookie-notice-seen';
    let acknowledged = false;

    function hasAcknowledged() {
        for (const name of ['localStorage', 'sessionStorage']) {
            try { acknowledged ||= window[name].getItem(key) === '1'; } catch (_) {}
        }
        return acknowledged;
    }

    function refreshNotice() {
        const notice = document.getElementById('vf-cookie-notice');
        if (notice) notice.hidden = hasAcknowledged();
    }

    document.addEventListener('click', event => {
        if (!event.target.closest('#vf-cookie-notice-dismiss')) return;
        acknowledged = true;
        // Persist across visits; session storage also covers browsers that block local storage.
        for (const name of ['localStorage', 'sessionStorage']) {
            try { window[name].setItem(key, '1'); } catch (_) {}
        }
        refreshNotice();
    });
    document.addEventListener('livewire:navigated', refreshNotice);
    window.addEventListener('storage', refreshNotice);
    refreshNotice();
})();
