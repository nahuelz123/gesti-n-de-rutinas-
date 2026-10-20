'use strict';
// No fetch handler: authenticated workouts, messages and health data are never cached.
self.addEventListener('push', event => {
    let payload = {};
    try { payload = event.data?.json() || {}; } catch (_) {}
    event.waitUntil(self.registration.showNotification('Descanso terminado', {
        body: 'Ya podés comenzar la siguiente serie.',
        vibrate: [160, 80, 160],
        icon: '/apple-touch-icon.png', badge: '/favicon-32x32.png',
        tag: typeof payload.tag === 'string' ? payload.tag : 'visionfit-rest',
        data: {url: self.location.origin + '/app/routines/active'},
    }));
});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = self.location.origin + '/app/routines/active';
    event.waitUntil(self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(async windows => {
        const existing = windows.find(client => client.url.startsWith(self.location.origin + '/app/'));
        if (existing) { await existing.focus(); return existing.navigate(url); }
        return self.clients.openWindow(url);
    }));
});
