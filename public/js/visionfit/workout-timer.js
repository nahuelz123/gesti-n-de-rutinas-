(function (root) {
    'use strict';

    function duration(value) {
        const seconds = Number(value);
        return Number.isFinite(seconds) && seconds > 0
            ? Math.min(3600, Math.max(1, Math.round(seconds)))
            : 90;
    }

    root.visionFitRestTimer = function ({ storageKey, defaultSeconds = 90 }) {
        return {
            defaultSeconds: duration(defaultSeconds),
            active: false,
            remaining: 0,
            displayTime: '',
            endAt: null,
            interval: null,
            pendingTimeouts: [],
            init() {
                this.showReady();
                let saved = null;
                try { saved = Number(root.sessionStorage.getItem(storageKey)); } catch (_) { /* Storage may be disabled. */ }
                if (Number.isFinite(saved) && saved > Date.now() && saved <= Date.now() + 3600000) {
                    this.endAt = saved;
                    this.active = true;
                    this.tick();
                    this.interval = setInterval(() => this.tick(), 250);
                } else {
                    this.clearSaved();
                }
            },
            format(seconds) {
                return Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
            },
            showReady() {
                this.remaining = this.defaultSeconds;
                this.displayTime = this.format(this.remaining);
            },
            updateDefaultSeconds(seconds) {
                this.defaultSeconds = duration(seconds);
                if (!this.active) this.showReady();
            },
            clearSaved() {
                try { root.sessionStorage.removeItem(storageKey); } catch (_) { /* Countdown works without storage. */ }
            },
            clearInterval() {
                if (this.interval !== null) clearInterval(this.interval);
                this.interval = null;
            },
            start(seconds = this.defaultSeconds) {
                this.clearInterval();
                this.endAt = Date.now() + duration(seconds) * 1000;
                this.active = true;
                try { root.sessionStorage.setItem(storageKey, String(this.endAt)); } catch (_) { /* Countdown works without storage. */ }
                this.tick();
                this.interval = setInterval(() => this.tick(), 250);
            },
            tick() {
                this.remaining = Math.max(0, Math.ceil((this.endAt - Date.now()) / 1000));
                this.displayTime = this.format(this.remaining);
                if (this.remaining === 0) {
                    this.clearInterval();
                    this.clearSaved();
                    this.endAt = null;
                    this.active = false;
                    this.displayTime = '¡Listo!';
                    if (root.navigator && typeof root.navigator.vibrate === 'function') {
                        root.navigator.vibrate([160, 80, 160]);
                    }
                }
            },
            stop() {
                this.clearInterval();
                this.clearSaved();
                this.endAt = null;
                this.active = false;
                this.showReady();
            },
            later(callback, delay) {
                const timeout = setTimeout(() => {
                    this.pendingTimeouts = this.pendingTimeouts.filter(id => id !== timeout);
                    callback();
                }, delay);
                this.pendingTimeouts.push(timeout);
            },
            handleSetLogged(payload = {}) {
                const setNumber = Number(payload.set || 0);
                const nextSetNumber = Number(payload.nextSet || 0);
                const row = document.getElementById('set-' + setNumber);
                document.querySelectorAll('.vf-set.next-up').forEach(element => element.classList.remove('next-up'));
                if (row) {
                    row.style.transform = 'scale(.99)';
                    this.later(() => { row.style.transform = 'scale(1)'; }, 140);
                }
                if (nextSetNumber) {
                    const nextRow = document.getElementById('set-' + nextSetNumber);
                    if (nextRow) {
                        nextRow.classList.add('next-up');
                        this.later(() => {
                            nextRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            document.getElementById('weight-' + nextSetNumber)?.focus({ preventScroll: true });
                        }, 220);
                    }
                }
                this.updateDefaultSeconds(payload.rest);
                this.start();
            },
            destroy() {
                // Preserve the deadline so returning from progress/tutorial resumes rest.
                this.clearInterval();
                this.pendingTimeouts.forEach(timeout => clearTimeout(timeout));
                this.pendingTimeouts = [];
            },
        };
    };
})(globalThis);
