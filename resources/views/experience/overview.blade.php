@php
    $firstName = explode(' ', trim($displayName))[0] ?: 'atleta';
    $levelPercent = max(0, min(100, (int) $experience['level_percent']));
    $unlockedAchievements = collect($experience['achievements'])->where('unlocked', true)->count();
    $nextExercise = $nextExercise ?? null;
    $workoutCompleted = $workoutCompleted ?? false;
    $hasAssignedRoutine = $hasAssignedRoutine ?? false;
    $restValue = trim((string) ($nextExercise['rest'] ?? ''));
    $restLabel = $restValue === '' ? 'Según tu entrenador' : (is_numeric($restValue) ? $restValue.' s' : $restValue);
@endphp

<x-layouts.experience :is-demo="$isDemo" :display-name="$displayName">
    <main class="vf-experience vf-focus-home" x-data="visionFitExperience({ storageKey: @js($avatarStorageKey), level: @js($experience['level']), isDemo: @js($isDemo), initialMuscleMap: @js($initialMuscleMap), demoExercises: @js($demoExercises) })" :data-theme="theme">
        @if($isDemo)
            <div class="vf-demo-strip"><span class="vf-demo-label"><i aria-hidden="true"></i> Modo Demo</span><span>Datos ficticios. Explorá sin guardar actividad real.</span></div>
        @endif

        <header class="vf-page-header">
            <div>
                <p class="vf-eyebrow">Hola, {{ $firstName }}</p>
                <h1>Tu entrenamiento, <span>a un toque.</span></h1>
                <p class="vf-page-subtitle">Tu rutina, tus pesos y tu progreso. Todo a mano.</p>
            </div>
        </header>

        <div class="vf-training-first-grid">
            <section id="vf-demo-lab" class="vf-panel vf-training-card vf-focus-workout" aria-labelledby="vf-training-heading">
                <div class="vf-training-kicker">
                    <span class="vf-eyebrow">{{ $workoutCompleted ? 'Sesión registrada' : ($experience['today_sets'] > 0 ? 'Seguimos donde quedaste' : 'Tu próxima sesión') }}</span>
                    <span class="vf-training-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m13 2-9 12h7l-1 8 10-13h-8z"/></svg></span>
                </div>
                <h2 id="vf-training-heading">{{ $workoutTitle }}</h2>
                <p>{{ $workoutSubtitle }}</p>

                @if($isDemo)
                    <button type="button" class="vf-primary-button" data-testid="demo-complete-set" @click="simulateSet()" :disabled="simulatedSets >= demoSetLimit">
                        <span x-text="simulatedSets >= demoSetLimit ? 'Prueba completada' : (simulatedSets ? 'Completar otra serie de prueba' : 'Probar entrenamiento')">Probar entrenamiento</span>
                        <span class="vf-mono" x-text="simulatedSets + '/' + demoSetLimit">0/3</span>
                    </button>
                    <p class="vf-demo-feedback" aria-live="polite" x-text="demoStatus || 'Podés simular 3 series. Tus registros y tu XP real no cambian.'">Podés simular 3 series. Tus registros y tu XP real no cambian.</p>
                @else
                    <a href="{{ $trainingUrl }}" class="vf-primary-button"><span>{{ $nextExercise ? 'Continuar mi rutina' : ($workoutCompleted ? 'Revisar mi rutina' : 'Ver mis rutinas') }}</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>
                    <p class="vf-workout-helper">{{ $nextExercise ? 'Abrí el ejercicio pendiente. El cronómetro y tus series están ahí.' : ($workoutCompleted ? 'Tus registros quedan disponibles para consultar tu progreso.' : ($hasAssignedRoutine ? 'Tu entrenador puede completar esta rutina desde su cuenta.' : 'Cuando te asignen una rutina, vas a verla acá.')) }}</p>
                @endif

                @if($nextExercise)
                    <div class="vf-next-exercise">
                        <span class="vf-next-label">Ejercicio pendiente</span>
                        <h3>{{ $nextExercise['title'] }}</h3>
                        <div class="vf-workout-prescription">
                            <span><strong>{{ $nextExercise['sets'] }}</strong> series</span>
                            <span><strong>{{ $nextExercise['reps'] }}</strong> reps</span>
                            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 2h6m-3 0v3m6 2 2-2m-8 4v5l3 2m5-2a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>{{ $restLabel }} de descanso</span>
                        </div>
                    </div>
                @elseif($workoutCompleted)
                    <div class="vf-next-exercise"><h3>Entrenamiento registrado.</h3><p class="vf-workout-helper">Ya completaste las series previstas para este día. Podés revisar los pesos y repeticiones de tu sesión.</p></div>
                @elseif($hasAssignedRoutine)
                    <div class="vf-next-exercise vf-unassigned"><h3>Tu rutina todavía no tiene ejercicios.</h3><p>Consultá con tu entrenador para completar los días de entrenamiento.</p></div>
                @else
                    <div class="vf-next-exercise vf-unassigned"><h3>Todavía no tenés una rutina asignada.</h3><p>Tu entrenador puede prepararla y asignártela desde su cuenta.</p></div>
                @endif


            </section>

            <section class="vf-panel vf-week-card vf-focus-week" aria-labelledby="vf-week-heading">
                <div class="vf-section-heading"><h2 id="vf-week-heading">Tu semana</h2><span class="vf-status-tag">Actividad registrada</span></div>
                <div class="vf-week-summary"><strong>{{ $experience['week_days'] }}<span>/ 7</span></strong><p>días con actividad<br><span class="vf-muted">esta semana</span></p></div>
                <div class="vf-week-strip" aria-label="Actividad de esta semana">
                    @foreach($weekActivity as $day)
                        <div class="vf-week-day {{ $day['active'] ? 'is-active' : '' }} {{ ($day['today'] ?? false) ? 'is-today' : '' }}">
                            <span>{{ $day['label'] }}</span>
                            <span class="vf-day-cell" aria-label="{{ $day['label'] }}: {{ $day['active'] ? 'con actividad' : 'sin registros' }}{{ ($day['today'] ?? false) ? ', hoy' : '' }}">
                                @if($day['active'])
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12 4 4 8-8"/></svg>
                                @else
                                    <span aria-hidden="true"></span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="vf-today-count"><strong>{{ $experience['today_sets'] }}</strong><span>series registradas hoy</span></div>
                <p class="vf-footnote">A tu ritmo. Descansar también es parte de entrenar.</p>
            </section>
        </div>

        <div class="vf-shortcuts vf-focus-shortcuts">
            <a href="{{ $progressUrl }}"><span class="vf-shortcut-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h16M8 14l4-5 3 2 5-6"/></svg></span><span>Mi progreso<small>Medidas y evolución</small></span><svg class="vf-small-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
            <a href="{{ $nutritionUrl }}"><span class="vf-shortcut-icon vf-shortcut-icon--lime"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3v6a3 3 0 0 0 6 0V3M8 3v18m10-18c-3 3-3 8 0 9h2V3h-2Zm2 9v9"/></svg></span><span>Nutrición<small>Comidas y recetas</small></span><svg class="vf-small-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
        </div>

        <div class="vf-support-grid">
            <section class="vf-panel vf-recent-card" aria-labelledby="vf-recent-heading">
                <div class="vf-section-heading"><div><p class="vf-eyebrow">{{ $isDemo ? 'Historial de ejemplo' : 'Actividad registrada' }}</p><h2 id="vf-recent-heading">Tus últimos pesos</h2></div><span class="vf-history-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11a9 9 0 1 1 3 8M3 4v7h7m2-4v5l3 2"/></svg></span></div>
                @forelse(array_slice($experience['recent_exercises'], 0, 3) as $entry)
                    <div class="vf-history-row">
                        <span class="vf-history-dot" aria-hidden="true"></span>
                        <div><strong>{{ $entry['title'] }}</strong><span>{{ \Illuminate\Support\Carbon::parse($entry['logged_at'])->format('d/m · H:i') }}</span></div>
                        <span class="vf-set-result">{{ $entry['weight'] !== null ? rtrim(rtrim(number_format((float) $entry['weight'], 2, ',', ''), '0'), ',').' kg' : 'Corporal' }}<small>{{ $entry['reps'] }} reps</small></span>
                    </div>
                @empty
                    <div class="vf-empty-state"><h3>Tu primera serie empieza el historial.</h3><p>Cuando registres tu entrenamiento, vas a encontrar acá los pesos y repeticiones que hiciste.</p></div>
                @endforelse
                <a href="{{ $progressUrl }}" class="vf-text-link">Ver mi progreso <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>
            </section>

            <section class="vf-panel vf-character-teaser" aria-labelledby="vf-character-heading">
                <div class="vf-section-heading"><h2 id="vf-character-heading">Mi personaje</h2><span class="vf-mini-level" x-show="showHomeProgress" x-cloak>Nivel {{ $experience['level'] }}</span></div>
                <div class="vf-character-preview">
                    <x-client.muscle-avatar :primary="$initialMuscleMap['primary']" :secondary="$initialMuscleMap['secondary']" :label="$initialMuscleMap['label']" :available="$initialMuscleMap['available']" :inherit-state="true" :compact="true" :show-controls="false" />
                    <div><p>Tu figura, tus colores y el mapa muscular de cada ejercicio.</p><a href="{{ $characterUrl }}" class="vf-text-link">Ver mi personaje <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a></div>
                </div>
                <label class="vf-home-progress-toggle"><input type="checkbox" :checked="showHomeProgress" @change="setHomeProgress($event.target.checked)"><span>Mostrar niveles y logros en Inicio</span></label>
                <div class="vf-home-progress" x-show="showHomeProgress" x-cloak>
                    <div class="vf-progress-heading"><strong>Nivel {{ $experience['level'] }}</strong><span class="vf-mono">{{ $experience['level_xp'] }} / {{ $experience['next_level_xp'] }} XP</span></div>
                    <div class="vf-xp-track" role="progressbar" aria-label="Experiencia del nivel actual" aria-valuenow="{{ $experience['level_xp'] }}" aria-valuemin="0" aria-valuemax="{{ $experience['next_level_xp'] }}"><span style="width: {{ $levelPercent }}%"></span></div>
                    <a href="{{ $characterUrl }}" class="vf-text-link">{{ $unlockedAchievements }} logros desbloqueados <span aria-hidden="true">→</span></a>
                </div>
            </section>
        </div>
    </main>
</x-layouts.experience>
