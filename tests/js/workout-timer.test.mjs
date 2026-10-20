import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const script = new URL('../../public/js/visionfit/workout-timer.js', import.meta.url);
function browser(saved = new Map(), storageUnavailable = false) {
    let now = 1_000_000;
    let id = 0;
    const intervals = new Map();
    const timeouts = new Map();
    const elements = new Map();
    const context = vm.createContext({
        Date: class extends Date { static now() { return now; } },
        sessionStorage: { getItem: key => { if (storageUnavailable) throw new Error('blocked'); return saved.get(key) ?? null; }, setItem: (key, value) => { if (storageUnavailable) throw new Error('blocked'); saved.set(key, String(value)); }, removeItem: key => { if (storageUnavailable) throw new Error('blocked'); saved.delete(key); } },
        setInterval: fn => { intervals.set(++id, fn); return id; },
        clearInterval: key => intervals.delete(key),
        setTimeout: fn => { timeouts.set(++id, fn); return id; },
        clearTimeout: key => timeouts.delete(key),
        document: { getElementById: key => elements.get(key), querySelectorAll: () => [] },
        navigator: {},
    });
    vm.runInContext(fs.readFileSync(script, 'utf8'), context);
    return {
        create: (options = {}) => { const timer = context.visionFitRestTimer({ storageKey: 'user-1-assignment-2', defaultSeconds: 60, ...options }); timer.init(); return timer; },
        advance: ms => { now += ms; for (const tick of [...intervals.values()]) tick(); },
        flush: () => { for (const fn of [...timeouts.values()]) fn(); timeouts.clear(); },
        setPush: push => { context.visionFitPush = push; },
        saved, intervals, timeouts, elements,
    };
}

test('idle timer is ready and manual start uses prescribed rest', () => {
    const b = browser(); const timer = b.create();
    assert.equal(timer.active, false); assert.equal(timer.displayTime, '1:00');
    timer.start();
    assert.equal(timer.active, true); assert.equal(timer.remaining, 60);
    assert.equal(b.saved.get('user-1-assignment-2'), '1060000');
});

test('deadline countdown catches up after background throttling and finishes', () => {
    const b = browser(); const timer = b.create(); timer.start();
    b.advance(45_500); assert.equal(timer.remaining, 15); assert.equal(timer.displayTime, '0:15');
    b.advance(20_000); assert.equal(timer.active, false); assert.equal(timer.displayTime, '¡Listo!');
    assert.equal(b.intervals.size, 0); assert.equal(b.saved.size, 0);
});

test('destroy releases intervals and reinitializing restores only the saved deadline', () => {
    const b = browser(); const first = b.create(); first.start(); b.advance(20_000);
    first.destroy(); assert.equal(b.intervals.size, 0); assert.equal(b.saved.size, 1);
    b.advance(10_000); const restored = b.create();
    assert.equal(restored.active, true); assert.equal(restored.remaining, 30);
    assert.equal(b.intervals.size, 1);
});

test('zero or missing rest uses 90 seconds and durations are bounded', () => {
    const b = browser(); const timer = b.create({ defaultSeconds: 0 });
    assert.equal(timer.displayTime, '1:30'); timer.start(); assert.equal(timer.remaining, 90);
    timer.handleSetLogged({ rest: 0 }); assert.equal(timer.remaining, 90);
    timer.updateDefaultSeconds(999999); timer.start(); assert.equal(timer.remaining, 3600);
    timer.updateDefaultSeconds(0.1); timer.start(); assert.equal(timer.remaining, 1);
    timer.updateDefaultSeconds(-10); timer.start(); assert.equal(timer.remaining, 90);
});

test('cancel clears persistence and lets manual start run again', () => {
    const b = browser(); const timer = b.create(); timer.start(); timer.stop();
    assert.equal(timer.active, false); assert.equal(timer.displayTime, '1:00');
    assert.equal(b.saved.size, 0); assert.equal(b.intervals.size, 0);
    timer.start(); assert.equal(timer.remaining, 60); assert.equal(b.intervals.size, 1);
});

test('exercise rest changes do not interrupt a running countdown', () => {
    const b = browser(); const timer = b.create(); timer.start(); b.advance(10_000);
    timer.updateDefaultSeconds(120); assert.equal(timer.remaining, 50);
    b.advance(10_000); assert.equal(timer.remaining, 40);
    timer.stop(); assert.equal(timer.displayTime, '2:00'); timer.start(); assert.equal(timer.remaining, 120);
});

test('saved set starts automatically and highlights, scrolls and focuses the next set', () => {
    const b = browser(); let highlighted = false; let scrolled = false; let focused = false;
    b.elements.set('set-1', { style: {} });
    b.elements.set('set-2', { classList: { add: name => { highlighted = name === 'next-up'; } }, scrollIntoView: () => { scrolled = true; } });
    b.elements.set('weight-2', { focus: () => { focused = true; } });
    const timer = b.create(); timer.handleSetLogged({ set: 1, nextSet: 2, rest: 75 }); b.flush();
    assert.equal(timer.remaining, 75); assert.equal(highlighted && scrolled && focused, true);
});

test('expired deadlines are removed without starting a countdown', () => {
    const b = browser(new Map([['user-1-assignment-2', '999999']])); const timer = b.create();
    assert.equal(timer.active, false); assert.equal(b.saved.size, 0);
    timer.start(); timer.destroy(); assert.equal(b.intervals.size, 0);
});


test('storage being unavailable does not block countdown or cancel', () => {
    const b = browser(new Map(), true); const timer = b.create(); timer.start();
    b.advance(10_000); assert.equal(timer.remaining, 50);
    timer.stop(); assert.equal(timer.active, false); assert.equal(b.intervals.size, 0);
});

test('restarting a set replaces the interval and stored deadline', () => {
    const b = browser(); const timer = b.create(); timer.start(); b.advance(10_000);
    timer.handleSetLogged({ rest: 120 });
    assert.equal(timer.remaining, 120); assert.equal(b.intervals.size, 1);
    assert.equal(b.saved.get('user-1-assignment-2'), '1130000');
    timer.destroy(); assert.equal(b.timeouts.size, 0);
});

test('another assignment cannot restore this timer', () => {
    const b = browser(); const timer = b.create(); timer.start();
    const another = b.create({ storageKey: 'user-1-assignment-3' });
    assert.equal(another.active, false); assert.equal(timer.active, true);
});

test('start cancel and restored rest synchronize their deadlines with server push', () => {
    const b = browser(); const calls = [];
    // Browser bridge is injected by push-alerts.js; timer must tolerate it being absent.
    const timer = b.create();
    timer.syncPush = (deadline, sequence) => calls.push({deadline, sequence});
    timer.start(); timer.stop();
    assert.equal(calls.length, 2);
    assert.equal(calls[0].deadline, 1060000); assert.equal(calls[1].deadline, null);
    assert.ok(calls[1].sequence > calls[0].sequence);
});


test('expired push scheduling reports the rest ended without promising an alert', async () => {
    const b = browser();
    b.setPush({schedule: async () => 'expired'});
    const timer = b.create({assignmentId: 2});
    await timer.syncPush(999999, timer.sequence);
    assert.equal(timer.pushMessage, 'El descanso terminó; no se programó un aviso.');
});
