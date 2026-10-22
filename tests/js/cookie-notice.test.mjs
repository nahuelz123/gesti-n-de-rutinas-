import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/js/visionfit/cookie-notice.js', import.meta.url), 'utf8');

function browser(local = new Map(), session = new Map()) {
    let notice;
    let dismiss;
    const listeners = {};
    const storage = map => ({getItem: key => map.get(key) ?? null, setItem: (key, value) => map.set(key, value)});
    const document = {
        readyState: 'complete',
        getElementById: id => id === 'vf-cookie-notice' ? notice : dismiss,
        addEventListener: (name, callback) => (listeners[name] ??= []).push(callback),
    };
    const window = {localStorage: storage(local), sessionStorage: storage(session), addEventListener: (name, cb) => (listeners[name] ??= []).push(cb)};
    const context = vm.createContext({window, document});
    function page() {
        notice = {hidden: true};
        dismiss = {addEventListener: (name, callback) => dismiss.click = callback};
        for (const cb of listeners['livewire:navigated'] ?? []) cb();
        return notice;
    }
    page();
    vm.runInContext(source, context);
    return {
        notice: () => notice,
        page,
        dismiss() {
            dismiss.click?.();
            for (const cb of listeners.click ?? []) cb({target: {closest: selector => selector === '#vf-cookie-notice-dismiss' ? dismiss : null}});
        },
    };
}

test('acknowledgement survives a new visit with a new tab session', () => {
    const persistent = new Map();
    const first = browser(persistent);
    assert.equal(first.notice().hidden, false);
    first.dismiss();
    assert.equal(first.notice().hidden, true);
    assert.equal(browser(persistent).notice().hidden, true);
});

test('Livewire navigation initializes a new notice and keeps it dismissed after acknowledgement', () => {
    const b = browser();
    assert.equal(b.page().hidden, false);
    b.dismiss();
    assert.equal(b.page().hidden, true);
});
