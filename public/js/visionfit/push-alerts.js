(function (root) {
    'use strict';
    let subscriptionId = null;
    let ready = null;
    let serial = Promise.resolve();
    const message = 'En iPhone: abrí VisionFit en Safari, Compartir → Añadir a pantalla de inicio. Abrí esa app y activá los avisos (iOS 16.4 o posterior).';
    const supported = () => root.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in root && 'Notification' in root;
    async function request(path, method = 'GET', body) {
        const response = await fetch('/app/' + path, {method, credentials: 'same-origin', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, ...(body ? {body: JSON.stringify(body)} : {})});
        if (!response.ok) throw new Error('No se pudo confirmar el aviso. Revisá tu conexión y volvé a intentarlo.');
        return response.json();
    }
    async function register(subscription) {
        if (!subscription) return false;
        const result = await request('push/subscriptions', 'POST', subscription.toJSON());
        subscriptionId = result.id;
        return true;
    }
    async function initialize() {
        if (!supported()) return false;
        await navigator.serviceWorker.register('/sw.js', {scope: '/'});
        const registration = await navigator.serviceWorker.ready;
        return register(await registration.pushManager.getSubscription());
    }
    root.visionFitPush = {
        async enable() {
            const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            const standalone = root.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
            if (ios && !standalone) throw new Error(message);
            if (!supported()) throw new Error(message + ' En otros equipos, usá un navegador compatible con notificaciones web.');
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') throw new Error('Avisos bloqueados. Permití las notificaciones de VisionFit en Ajustes y volvé a intentarlo.');
            await navigator.serviceWorker.register('/sw.js', {scope: '/'});
            const registration = await navigator.serviceWorker.ready;
            const {publicKey} = await request('push/key');
            const bytes = Uint8Array.from(atob(publicKey.replace(/-/g, '+').replace(/_/g, '/').padEnd(Math.ceil(publicKey.length / 4) * 4, '=')), c => c.charCodeAt(0));
            await register(await registration.pushManager.getSubscription() || await registration.pushManager.subscribe({userVisibleOnly: true, applicationServerKey: bytes}));
            ready = Promise.resolve(true);
        },
        async disable() {
            if (!supported()) return;
            const registration = await navigator.serviceWorker.getRegistration('/');
            const subscription = await registration?.pushManager.getSubscription();
            if (!subscription) return;
            try { await request('push/subscriptions', 'DELETE', {endpoint: subscription.endpoint}); }
            finally { await subscription.unsubscribe(); subscriptionId = null; ready = null; }
        },
        schedule(assignmentId, deadline, sequence) {
            const operation = serial.catch(() => {}).then(async () => {
                if (!ready) ready = initialize().catch(() => { ready = null; return false; });
                if (!await ready || !subscriptionId) return false;
                const scheduledDeadline = deadline && deadline > Date.now() ? deadline : null;
                try {
                    await request('rest-alerts/' + assignmentId, 'PUT', {subscription_id: subscriptionId, sequence, deadline: scheduledDeadline});
                } catch (error) { ready = null; subscriptionId = null; throw error; }
                return scheduledDeadline ? 'scheduled' : (deadline ? 'expired' : 'cancelled');
            });
            serial = operation;
            return operation;
        },
    };
    document.addEventListener?.('submit', event => {
        if (!event.target.matches('[data-push-logout]')) return;
        event.preventDefault();
        const form = event.target;
        Promise.race([root.visionFitPush.disable().catch(() => {}), new Promise(resolve => setTimeout(resolve, 2500))]).finally(() => form.submit());
    });
})(globalThis);
