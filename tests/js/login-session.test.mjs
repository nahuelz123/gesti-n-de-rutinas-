import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/js/visionfit/login-session.js', import.meta.url), 'utf8');

function browser({fail = false, wait = false} = {}) {
    const listeners = {};
    const calls = [];
    const submitted = [];
    let complete;
    const delayed = new Promise(resolve => complete = resolve);
    const token = {value: 'expired-token'};
    const button = {disabled: false};
    const error = {hidden: true, textContent: ''};
    const form = {
        isConnected: true,
        dataset: {sessionUrl: '/auth/session'},
        email: 'client@example.test', password: 'secret', remember: true,
        matches: selector => selector === '[data-vf-login]',
        querySelector: selector => selector === '[name="_token"]' ? token : error,
        querySelectorAll: () => [button],
    };
    const document = {
        addEventListener: (name, cb) => listeners[name] = cb,
        querySelectorAll: () => [form],
    };
    const context = vm.createContext({
        document, AbortController, setTimeout, clearTimeout,
        window: {addEventListener: (name, cb) => listeners[name] = cb},
        HTMLFormElement: {prototype: {submit() { submitted.push({token: token.value, remember: this.remember}); }}},
        fetch: async (url, options) => {
            calls.push({url, options});
            if (wait) await delayed;
            if (fail) throw new Error('network');
            return {ok: true, json: async () => ({token: 'fresh-session-token'})};
        },
    });
    vm.runInContext(source, context);
    return {form, calls, submitted, token, button, error,
        submit: () => listeners.submit?.({target: form, preventDefault() {}}),
        restore: () => listeners.pageshow?.({persisted: true}),
        navigate: () => listeners['livewire:navigating']?.(),
        back: () => listeners['livewire:navigated']?.(),
        complete,
    };
}

test('login refreshes a stale token before submitting and preserves the remember choice', async () => {
    const b = browser();
    await b.submit();
    assert.equal(b.calls[0]?.url, '/auth/session');
    assert.equal(b.calls[0]?.options.credentials, 'same-origin');
    assert.equal(b.calls[0]?.options.cache, 'no-store');
    assert.deepEqual(b.submitted, [{token: 'fresh-session-token', remember: true}]);
});

test('a failed session refresh does not send credentials and permits retry', async () => {
    const b = browser({fail: true});
    await b.submit();
    assert.equal(b.submitted.length, 0);
    assert.equal(b.form.password, 'secret');
    assert.equal(b.error.hidden, false);
    assert.equal(b.button.disabled, false);
    await b.submit();
    assert.equal(b.calls.length, 2);
});

test('double submit sends one login and a restored page can submit again', async () => {
    const b = browser();
    await Promise.all([b.submit(), b.submit()]);
    assert.equal(b.submitted.length, 1);
    b.restore();
    await b.submit();
    assert.equal(b.submitted.length, 2);
});

test('Livewire navigation cancels pending login and restores an enabled form on Back', async () => {
    const b = browser({wait: true});
    const submitting = b.submit();
    assert.equal(b.button.disabled, true);
    b.navigate();
    b.complete();
    await submitting;
    assert.equal(b.submitted.length, 0);
    b.button.disabled = true; // Simulate a disabled form restored from Livewire's snapshot.
    b.back();
    assert.equal(b.button.disabled, false);
    await b.submit();
    assert.equal(b.submitted.length, 1);
});
