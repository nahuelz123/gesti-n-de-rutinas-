import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const script = new URL('../../public/js/visionfit/experience.js', import.meta.url);

function browser(saved = new Map(), blocked = false) {
    const context = vm.createContext({
        localStorage: {
            getItem(key) { if (blocked) throw new Error('blocked'); return saved.get(key) ?? null; },
            setItem(key, value) { if (blocked) throw new Error('blocked'); saved.set(key, value); },
        },
    });
    if (fs.existsSync(script)) vm.runInContext(fs.readFileSync(script, 'utf8'), context);
    return {
        saved,
        create(options = {}) {
            assert.equal(typeof context.visionFitExperience, 'function', 'experience state factory is available');
            const state = context.visionFitExperience({ storageKey: 'visionfit-avatar:user:12', level: 1, ...options });
            state.init();
            return state;
        },
    };
}

const exercises = [
    { id: 'press', title: 'Press de pecho', primary: ['chest'], secondary: ['triceps', 'shoulders'], available: true },
    { id: 'squat', title: 'Sentadilla', primary: ['quadriceps', 'glutes'], secondary: ['hamstrings', 'abs'], available: true },
    { id: 'unknown', title: 'Ejercicio sin mapa', primary: [], secondary: [], available: false },
];

test('real accounts can inspect only the maps supplied for their own recorded exercises', () => {
    const state = browser().create({ mapOptions: exercises, initialExerciseId: 'press', isDemo: false });
    assert.equal(state.muscleClass('chest'), 'is-primary');
    assert.equal(state.muscleClass('triceps'), 'is-secondary');
    assert.equal(state.selectExercise('squat'), true);
    assert.equal(state.muscleClass('quadriceps'), 'is-primary');
    assert.equal(state.selectExercise('foreign'), false);
    assert.equal(state.simulateSet(), false);
});

test('supports the three allowed avatars and rejects arbitrary appearance values', () => {
    const state = browser().create();
    assert.equal(state.avatar, 'generic');
    assert.equal(state.selectAvatar('male'), true);
    assert.equal(state.avatar, 'male');
    assert.equal(state.selectAvatar('female'), true);
    assert.equal(state.avatar, 'female');
    assert.equal(state.selectAvatar('custom<script>'), false);
    assert.equal(state.avatar, 'female');
    assert.equal(state.selectAvatar('generic'), true);
});

test('cosmetic themes unlock only at their server-provided level', () => {
    const novice = browser().create({ level: 1 });
    assert.equal(novice.theme, 'ion');
    assert.equal(novice.selectTheme('solar'), false);
    assert.equal(novice.selectTheme('nova'), false);
    assert.equal(novice.selectTheme('url(evil)'), false);
    assert.equal(novice.theme, 'ion');
    const intermediate = browser().create({ level: 3 });
    assert.equal(intermediate.selectTheme('solar'), true);
    assert.equal(intermediate.selectTheme('nova'), false);
    const advanced = browser().create({ level: 5 });
    assert.equal(advanced.selectTheme('nova'), true);
});

test('restores allowed choices while rejecting stale locked themes', () => {
    const saved = new Map([['visionfit-avatar:user:12', JSON.stringify({ avatar: 'female', theme: 'nova', level: 99 })]]);
    const state = browser(saved).create({ level: 1 });
    assert.equal(state.avatar, 'female');
    assert.equal(state.theme, 'ion');
    assert.equal(state.level, 1);
});

test('keeps account preferences independent and persists only display choices', () => {
    const b = browser();
    const first = b.create({ level: 3 });
    first.selectAvatar('male');
    first.selectTheme('solar');
    const second = b.create({ storageKey: 'visionfit-avatar:user:13', level: 3 });
    assert.equal(second.avatar, 'generic');
    assert.equal(second.theme, 'ion');
    const restored = b.create({ level: 3 });
    assert.equal(restored.avatar, 'male');
    assert.equal(restored.theme, 'solar');
    assert.deepEqual(JSON.parse(b.saved.get('visionfit-avatar:user:12')), { avatar: 'male', theme: 'solar', showHomeProgress: false });
});

test('blocked or malformed local storage leaves all controls usable', () => {
    const blocked = browser(new Map(), true).create({ level: 5 });
    assert.equal(blocked.selectAvatar('female'), true);
    assert.equal(blocked.selectTheme('nova'), true);
    assert.equal(blocked.setView('back'), true);
    const malformed = browser(new Map([['visionfit-avatar:user:12', '{bad json']])).create();
    assert.equal(malformed.avatar, 'generic');
    assert.equal(malformed.theme, 'ion');
});

test('front and back selection accepts only supported views', () => {
    const state = browser().create();
    assert.equal(state.view, 'front');
    assert.equal(state.setView('back'), true);
    assert.equal(state.view, 'back');
    assert.equal(state.setView('side'), false);
    assert.equal(state.view, 'back');
});

test('demo selection follows the selected exercise and never invents unknown muscles', () => {
    const state = browser().create({ isDemo: true, demoExercises: exercises });
    assert.equal(state.muscleClass('chest'), 'is-primary');
    assert.equal(state.muscleClass('triceps'), 'is-secondary');
    assert.equal(state.selectExercise('squat'), true);
    assert.equal(state.muscleClass('quadriceps'), 'is-primary');
    assert.equal(state.muscleClass('chest'), '');
    assert.equal(state.mapLabel, 'Sentadilla');
    assert.equal(state.selectExercise('unknown'), true);
    assert.equal(state.mapAvailable, false);
    assert.equal(state.muscleClass('chest'), '');
    assert.equal(state.selectExercise('missing'), false);
});

test('muscle maps discard unsupported IDs and primary muscles take precedence', () => {
    const state = browser().create({ initialMuscleMap: { primary: ['chest', 'dragon', 'chest'], secondary: ['chest', 'triceps', 'magic'], available: true, label: 'Press' } });
    assert.deepEqual([...state.primary], ['chest']);
    assert.deepEqual([...state.secondary], ['triceps']);
    assert.equal(state.muscleClass('dragon'), '');
    assert.equal(state.muscleClass('chest'), 'is-primary');
});

test('synthetic demo completion caps at three sets without changing real level or unlocks', () => {
    const b = browser();
    const state = b.create({ isDemo: true, demoSetLimit: 999, demoExercises: exercises, level: 1 });
    assert.equal(state.simulateSet(), true);
    assert.equal(state.simulatedSets, 1);
    assert.match(state.demoStatus, /simulada/i);
    state.simulateSet();
    state.simulateSet();
    assert.equal(state.simulateSet(), false);
    assert.equal(state.simulatedSets, 3);
    assert.equal(state.level, 1);
    assert.equal(state.themeUnlocked('solar'), false);
    assert.equal(b.saved.size, 0);
    assert.equal(state.selectExercise('squat'), true);
    assert.equal(state.simulatedSets, 3);
});

test('real account state cannot simulate activity or replace its server muscle map', () => {
    const state = browser().create({ isDemo: false, demoExercises: exercises, initialMuscleMap: { primary: ['back'], secondary: [], available: true } });
    assert.equal(state.simulateSet(), false);
    assert.equal(state.simulatedSets, 0);
    assert.equal(state.selectExercise('press'), false);
    assert.equal(state.muscleClass('back'), 'is-primary');
});

test('a shorter demo limit describes its actual completed synthetic set count', () => {
    const state = browser().create({ isDemo: true, demoSetLimit: 1 });
    assert.equal(state.simulateSet(), true);
    assert.equal(state.simulateSet(), false);
    assert.match(state.demoStatus, /1 serie simulada/);
    assert.doesNotMatch(state.demoStatus, /3 series/);
});

test('changing a client state level cannot unlock a server-locked theme', () => {
    const state = browser().create({ level: 1 });
    state.level = 99;
    assert.equal(state.selectTheme('nova'), false);
});

test('home levels and achievements are optional without resetting existing appearance', () => {
    const saved = new Map([['visionfit-avatar:user:12', JSON.stringify({ avatar: 'female', theme: 'solar' })]]);
    const state = browser(saved).create({ level: 3 });
    assert.equal(state.showHomeProgress, false);
    assert.equal(state.avatar, 'female');
    assert.equal(state.theme, 'solar');
    assert.equal(state.setHomeProgress(true), true);
    assert.equal(state.showHomeProgress, true);
    assert.equal(state.level, 3);
});

test('home progress visibility survives navigation and stays scoped to the account', () => {
    const b = browser();
    b.create().setHomeProgress(true);
    const restored = b.create();
    assert.equal(restored.showHomeProgress, true);
    assert.equal(b.create({ storageKey: 'visionfit-avatar:user:13' }).showHomeProgress, false);
    restored.selectAvatar('male');
    assert.equal(b.create().showHomeProgress, true);
    restored.setHomeProgress(false);
    assert.equal(b.create().showHomeProgress, false);
});

test('home progress controls tolerate blocked storage and reject non-boolean preferences', () => {
    const state = browser(new Map(), true).create();
    assert.equal(state.setHomeProgress(true), true);
    assert.equal(state.showHomeProgress, true);
    assert.equal(state.setHomeProgress('false'), false);
    assert.equal(state.showHomeProgress, true);
    const saved = new Map([['visionfit-avatar:user:12', JSON.stringify({ showHomeProgress: 'true' })]]);
    assert.equal(browser(saved).create().showHomeProgress, false);
});
