import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
function browser({ios = false, standalone = false, permission = 'granted'} = {}) {
    const calls = []; let requested = 0;
    const subscription = {toJSON: () => ({endpoint: 'https://web.push.apple.com/test', keys: {p256dh: 'key', auth: 'auth'}})};
    const registration = {pushManager: {getSubscription: async () => subscription, subscribe: async () => subscription}};
    const context = vm.createContext({isSecureContext: true, PushManager: class {}, Uint8Array, atob: value => Buffer.from(value, 'base64').toString('binary'),
        navigator: {userAgent: ios ? 'iPhone' : 'Chrome', serviceWorker: {register: async () => registration, ready: Promise.resolve(registration)}},
        Notification: {requestPermission: async () => {requested++; return permission;}}, matchMedia: () => ({matches: standalone}),
        document: {querySelector: () => ({content: 'csrf'})},
        fetch: async (url, options) => {calls.push({url, options}); return {ok: true, json: async () => url.endsWith('/key') ? {publicKey: 'aaaa'} : {id: 1}};},
    });
    vm.runInContext(fs.readFileSync(new URL('../../public/js/visionfit/push-alerts.js', import.meta.url), 'utf8'), context);
    return {push: context.visionFitPush, calls, requested: () => requested};
}
test('iPhone browser asks for Home Screen install before requesting permission', async () => {
    const b = browser({ios: true});
    await assert.rejects(b.push.enable(), /pantalla de inicio/);
    assert.equal(b.requested(), 0);
});
test('permission denial reports settings instructions and never registers subscription', async () => {
    const b = browser({permission: 'denied'});
    await assert.rejects(b.push.enable(), /Ajustes/);
    assert.equal(b.calls.length, 0);
});
test('start and cancellation register device and send authenticated ordered API requests', async () => {
    const b = browser();
    await Promise.all([b.push.schedule(10, Date.now() + 60000, 1), b.push.schedule(10, null, 2)]);
    const updates = b.calls.filter(call => call.url.includes('rest-alerts'));
    assert.equal(updates.length, 2);
    assert.ok(JSON.parse(updates[0].options.body).deadline > Date.now());
    assert.equal(JSON.parse(updates[1].options.body).deadline, null);
    assert.equal(updates[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.equal(updates[0].options.credentials, 'same-origin');
});
test('an expired deadline cancels the server alert and reports expired rather than scheduled', async () => {
    const b = browser();
    const outcome = await b.push.schedule(10, Date.now() - 1, 3);
    assert.equal(outcome, 'expired');
    const update = b.calls.find(call => call.url.includes('rest-alerts'));
    assert.equal(JSON.parse(update.options.body).deadline, null);
});
