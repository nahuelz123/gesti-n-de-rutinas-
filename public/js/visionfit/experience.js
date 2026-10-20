(function (root) {
    'use strict';

    const avatars = ['generic', 'male', 'female'];
    const themes = { ion: 1, solar: 3, nova: 5 };
    const muscles = ['chest', 'back', 'shoulders', 'biceps', 'triceps', 'forearms', 'abs', 'glutes', 'quadriceps', 'hamstrings', 'calves'];
    const muscleIds = value => Array.isArray(value) ? [...new Set(value.filter(id => muscles.includes(id)))] : [];

    root.visionFitExperience = function (config = {}) {
        // Progress is a server snapshot. Demo actions never change it or write training data.
        const level = Math.max(1, Math.floor(Number(config.level) || 1));
        const isDemo = config.isDemo === true;
        const storageKey = typeof config.storageKey === 'string' ? config.storageKey.trim() : '';
        const exercises = Array.isArray(config.demoExercises) ? config.demoExercises.filter(exercise => exercise && exercise.id != null) : [];
        const limit = Math.min(3, Math.max(1, Math.floor(Number(config.demoSetLimit) || 3)));

        return {
            avatar: 'generic',
            theme: 'ion',
            showHomeProgress: false,
            view: 'front',
            level,
            isDemo,
            primary: [],
            secondary: [],
            mapLabel: 'Mapa muscular',
            mapAvailable: false,
            selectedExerciseId: '',
            simulatedSets: 0,
            demoSetLimit: limit,
            demoStatus: '',
            init() {
                try {
                    const saved = storageKey ? JSON.parse(root.localStorage.getItem(storageKey)) : null;
                    if (saved && avatars.includes(saved.avatar)) this.avatar = saved.avatar;
                    if (saved && this.themeUnlocked(saved.theme)) this.theme = saved.theme;
                    if (saved && typeof saved.showHomeProgress === 'boolean') this.showHomeProgress = saved.showHomeProgress;
                } catch (_) { /* Appearance remains usable when local storage is unavailable. */ }
                this.applyMap(config.initialMuscleMap || {});
                if (isDemo && exercises.length) {
                    const initial = exercises.find(exercise => String(exercise.id) === String(config.initialExerciseId)) || exercises[0];
                    this.selectExercise(initial.id);
                }
            },
            persistPreferences() {
                if (!storageKey) return;
                try { root.localStorage.setItem(storageKey, JSON.stringify({ avatar: this.avatar, theme: this.theme, showHomeProgress: this.showHomeProgress })); } catch (_) {}
            },
            selectAvatar(avatar) {
                if (!avatars.includes(avatar)) return false;
                this.avatar = avatar;
                this.persistPreferences();
                return true;
            },
            themeUnlocked(theme) {
                return Object.prototype.hasOwnProperty.call(themes, theme) && level >= themes[theme];
            },
            selectTheme(theme) {
                if (!this.themeUnlocked(theme)) return false;
                this.theme = theme;
                this.persistPreferences();
                return true;
            },
            setHomeProgress(visible) {
                if (typeof visible !== 'boolean') return false;
                this.showHomeProgress = visible;
                this.persistPreferences();
                return true;
            },
            setView(view) {
                if (!['front', 'back'].includes(view)) return false;
                this.view = view;
                return true;
            },
            applyMap(map) {
                this.primary = muscleIds(map.primary);
                this.secondary = muscleIds(map.secondary).filter(id => !this.primary.includes(id));
                this.mapAvailable = map.available !== false && (this.primary.length + this.secondary.length > 0);
                this.mapLabel = typeof map.label === 'string' && map.label ? map.label : 'Mapa muscular';
            },
            muscleClass(id) {
                if (!this.mapAvailable) return '';
                if (this.primary.includes(id)) return 'is-primary';
                return this.secondary.includes(id) ? 'is-secondary' : '';
            },
            selectExercise(id) {
                if (!isDemo) return false;
                const exercise = exercises.find(item => String(item.id) === String(id));
                if (!exercise) return false;
                this.selectedExerciseId = String(exercise.id);
                this.applyMap({ ...exercise, label: exercise.label || exercise.title });
                return true;
            },
            simulateSet() {
                if (!isDemo || this.simulatedSets >= limit) return false;
                this.simulatedSets += 1;
                this.demoStatus = this.simulatedSets === limit
                    ? 'Demo completada: ' + limit + (limit === 1 ? ' serie simulada.' : ' series simuladas.') + ' Tu progreso real no cambió.'
                    : 'Serie simulada ' + this.simulatedSets + ' de ' + limit + '. Tu progreso real no cambió.';
                return true;
            },
        };
    };
})(globalThis);
