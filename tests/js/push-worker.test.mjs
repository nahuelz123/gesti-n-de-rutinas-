import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
test('push worker shows generic notification with safe same origin destination and no fetch caching', async () => {
    const handlers = {}; let shown;
    const context = vm.createContext({self: {location: {origin: 'https://fit.test'}, addEventListener: (name, cb) => handlers[name] = cb, registration: {showNotification: async (...args) => {shown = args;}}, clients: {}}, URL});
    vm.runInContext(fs.readFileSync(new URL('../../public/sw.js', import.meta.url), 'utf8'), context);
    let promise;
    handlers.push({data: {json: () => ({tag: 'rest-1', url: 'https://evil.test'})}, waitUntil: p => promise = p});
    await promise;
    assert.equal(shown[0], 'Descanso terminado');
    assert.equal(shown[1].data.url, 'https://fit.test/app/routines/active');
    assert.equal(handlers.fetch, undefined);
});
