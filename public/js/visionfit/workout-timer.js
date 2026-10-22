(function (root) {
    'use strict';

    function duration(value) {
        const seconds = Number(value);
        return Number.isFinite(seconds) && seconds > 0
            ? Math.min(3600, Math.max(1, Math.round(seconds)))
            : 90;
    }

    root.visionFitRestTimer = function ({ storageKey, defaultSeconds = 90, assignmentId = null }) {
        return {
            defaultSeconds: duration(defaultSeconds),
            active: false,
            minimized: false,
            remaining: 0,
            displayTime: '',
            endAt: null,
            interval: null,
            pendingTimeouts: [],
            sequence: 0,
            pushMessage: 'Para avisos con pantalla bloqueada, activá las notificaciones.',
            pushBusy: false,
            async enablePush() {
                this.pushBusy = true;
                try {
                    await root.visionFitPush.enable();
                    this.pushMessage = 'Avisos activados. El sonido y la vibración dependen de los ajustes del teléfono.';
                    if (this.active) this.syncPush(this.endAt, this.sequence);
                } catch (error) { this.pushMessage = error.message; }
                finally { this.pushBusy = false; }
            },
            nextSequence() {
                let saved = 0;
                try { saved = Number(root.localStorage?.getItem(storageKey + ':sequence')) || 0; } catch (_) {}
                this.sequence = Math.max(Date.now(), saved + 1, this.sequence + 1);
                try { root.localStorage?.setItem(storageKey + ':sequence', String(this.sequence)); } catch (_) {}
                return this.sequence;
            },
            syncPush(deadline, sequence) {
                if (assignmentId && root.visionFitPush) {
                    return root.visionFitPush.schedule(assignmentId, deadline, sequence).then(outcome => {
                        if (this.sequence !== sequence) return;
                        if (outcome === 'scheduled') this.pushMessage = 'Aviso de descanso programado.';
                        if (outcome === 'cancelled') this.pushMessage = 'Aviso cancelado.';
                        if (outcome === 'expired') this.pushMessage = 'El descanso terminó; no se programó un aviso.';
                    }).catch(() => { this.pushMessage = 'Sin conexión: no se pudo confirmar el aviso. Mantené la pantalla abierta.'; });
                }
            },
            init() {
                this.showReady();
                try { this.minimized = root.sessionStorage.getItem(storageKey + ':minimized') === 'true'; } catch (_) {}
                let saved = null;
                try { saved = Number(root.sessionStorage.getItem(storageKey)); } catch (_) { /* Storage may be disabled. */ }
                if (Number.isFinite(saved) && saved > Date.now() && saved <= Date.now() + 3600000) {
                    this.endAt = saved;
                    try { this.sequence = Number(root.localStorage?.getItem(storageKey + ':active-sequence')) || this.nextSequence(); } catch (_) { this.nextSequence(); }
                    this.syncPush(saved, this.sequence);
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
            minimize() {
                this.minimized = true;
                try { root.sessionStorage.setItem(storageKey + ':minimized', 'true'); } catch (_) {}
            },
            expand() {
                this.minimized = false;
                try { root.sessionStorage.removeItem(storageKey + ':minimized'); } catch (_) {}
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
                this.nextSequence();
                this.syncPush(this.endAt, this.sequence);
                try { root.localStorage?.setItem(storageKey + ':active-sequence', String(this.sequence)); } catch (_) {}
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
                this.syncPush(null, this.nextSequence());
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
