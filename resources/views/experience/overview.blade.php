@php
    $firstName = explode(' ', trim($displayName))[0] ?: 'atleta';
    $levelPercent = max(0, min(100, (int) $experience['level_percent']));
    $unlockedAchievements = collect($experience['achievements'])->where('unlocked', true)->count();
    $weekActivity = $weekActivity ?? [];
    $muscleLabels = ['chest' => 'Pecho', 'back' => 'Espalda', 'shoulders' => 'Hombros', 'biceps' => 'Bíceps', 'triceps' => 'Tríceps', 'forearms' => 'Antebrazos', 'abs' => 'Abdominales', 'glutes' => 'Glúteos', 'quadriceps' => 'Cuádriceps', 'hamstrings' => 'Isquiotibiales', 'calves' => 'Gemelos'];
@endphp

<x-layouts.experience :is-demo="$isDemo" :display-name="$displayName">
    <main class="vf-experience" x-data="visionFitExperience({ storageKey: @js($avatarStorageKey), level: @js($experience['level']), isDemo: @js($isDemo), initialMuscleMap: @js($initialMuscleMap), demoExercises: @js($demoExercises) })" :data-theme="theme">
        @if($isDemo)
            <div class="vf-demo-strip"><span class="vf-demo-label"><i aria-hidden="true"></i> Modo demo</span><span>Datos ficticios. Explorá sin guardar actividad real.</span></div>
        @endif

        <header class="vf-page-header">
            <div><p class="vf-eyebrow">Hola, {{ $firstName }} <span class="vf-header-slash" aria-hidden="true">/</span> Este es tu espacio</p><h1>Tu entrenamiento,<br class="vf-mobile-break"> <span>en foco.</span></h1><p class="vf-page-subtitle">Cada día que entrenás cuenta. También los de descanso.</p></div>
            <a href="{{ $characterUrl }}" class="vf-text-link vf-header-link">Mi personaje <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg></a>
        </header>

        <div class="vf-command-grid">
            <section id="vf-avatar-map" class="vf-panel vf-avatar-card" aria-labelledby="vf-avatar-heading">
                <div class="vf-panel-heading"><div><p class="vf-eyebrow">Tu personaje</p><h2 id="vf-avatar-heading">La constancia toma forma.</h2></div><span class="vf-level-chip"><span>NV</span> {{ str_pad((string) $experience['level'], 2, '0', STR_PAD_LEFT) }}</span></div>

                @if($isDemo)
                    <div class="vf-character-exercise"><label for="vf-demo-exercise">Ejercicio de ejemplo</label><select id="vf-demo-exercise" :value="selectedExerciseId" @change="selectExercise($event.target.value)">@foreach($demoExercises as $exercise)<option value="{{ $exercise['id'] }}">{{ $exercise['title'] }}</option>@endforeach</select></div>
                @endif

                <x-client.muscle-avatar :primary="$initialMuscleMap['primary']" :secondary="$initialMuscleMap['secondary']" :label="$initialMuscleMap['label']" :available="$initialMuscleMap['available']" :inherit-state="true" />

                <div class="vf-avatar-selector" role="group" aria-label="Figura del personaje">
                    <span class="vf-selector-label">Figura</span>
                    <button type="button" @click="selectAvatar('generic')" :aria-pressed="avatar === 'generic'" :class="{ 'is-selected': avatar === 'generic' }">Genérica</button>
                    <button type="button" @click="selectAvatar('male')" :aria-pressed="avatar === 'male'" :class="{ 'is-selected': avatar === 'male' }">Masculina</button>
                    <button type="button" @click="selectAvatar('female')" :aria-pressed="avatar === 'female'" :class="{ 'is-selected': avatar === 'female' }">Femenina</button>
                </div>

                <div class="vf-level-progress">
                    <div class="vf-progress-heading"><span><strong>Nivel {{ $experience['level'] }}</strong> <span class="vf-muted">/ Tu recorrido</span></span><span class="vf-mono">{{ $experience['level_xp'] }} <span class="vf-muted">/ {{ $experience['next_level_xp'] }} XP</span></span></div>
                    <div class="vf-xp-track" role="progressbar" aria-label="Experiencia del nivel actual" aria-valuenow="{{ $experience['level_xp'] }}" aria-valuemin="0" aria-valuemax="{{ $experience['next_level_xp'] }}"><span style="width: {{ $levelPercent }}%"></span></div>
                    <p>100 XP por día con actividad registrada. A tu ritmo.</p>
                </div>
            </section>

            <div class="vf-command-stack">
                <section class="vf-panel vf-training-card" aria-labelledby="vf-training-heading">
                    <div class="vf-training-kicker"><span class="vf-eyebrow">Tu próximo paso</span><span class="vf-training-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m13 2-9 12h7l-1 8 10-13h-8z"/></svg></span></div>
                    <h2 id="vf-training-heading">{{ $workoutTitle }}</h2>
                    <p>{{ $workoutSubtitle }}</p>
                    <div class="vf-training-detail"><span class="vf-live-dot" aria-hidden="true"></span><span>{{ $experience['today_sets'] > 0 ? $experience['today_sets'].' series registradas hoy' : 'Todo listo para tu próxima sesión' }}</span></div>
                    <a href="{{ $trainingUrl }}" class="vf-primary-button"><span>{{ $isDemo ? 'Probar entrenamiento' : 'Ir a mi rutina' }}</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>
                </section>

                <section class="vf-panel vf-week-card" aria-labelledby="vf-week-heading">
                    <div class="vf-section-heading"><h2 id="vf-week-heading">Tu semana</h2><span class="vf-status-tag">En movimiento</span></div>
                    <div class="vf-week-summary"><strong>{{ $experience['week_days'] }}<span>/ 7</span></strong><p>días con actividad<br><span class="vf-muted">esta semana</span></p></div>
                    @if(count($weekActivity))
                        <div class="vf-week-strip" aria-label="Actividad de esta semana">
                            @foreach($weekActivity as $day)
                                <div class="vf-week-day {{ $day['active'] ? 'is-active' : '' }} {{ ($day['today'] ?? false) ? 'is-today' : '' }}"><span>{{ $day['label'] }}</span><span class="vf-day-cell" aria-label="{{ $day['label'] }}: {{ $day['active'] ? 'con actividad' : 'sin registros' }}{{ ($day['today'] ?? false) ? ', hoy' : '' }}">@if($day['active'])<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12 4 4 8-8"/></svg>@else<span aria-hidden="true"></span>@endif</span></div>
                            @endforeach
                        </div>
                    @endif
                    <p class="vf-footnote">La constancia se construye en semanas, no en rachas perfectas.</p>
                </section>

                <div class="vf-shortcuts">
                    <a href="{{ $progressUrl }}"><span class="vf-shortcut-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h16M8 14l4-5 3 2 5-6"/></svg></span><span>Mi progreso<small>Tu historial real</small></span><svg class="vf-small-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
                    <a href="{{ $nutritionUrl }}"><span class="vf-shortcut-icon vf-shortcut-icon--lime"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3v6a3 3 0 0 0 6 0V3M8 3v18m10-18c-3 3-3 8 0 9h2V3h-2Zm2 9v9"/></svg></span><span>Nutrición<small>Tu plan, a mano</small></span><svg class="vf-small-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
                </div>
            </div>
        </div>

        <section class="vf-metrics-row" aria-label="Tu actividad acumulada">
            <div><span class="vf-metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4m8-4v4M4 10h16M5 4h14a1 1 0 0 1 1 1v15H4V5a1 1 0 0 1 1-1Zm3 11h3"/></svg></span><span><strong>{{ $experience['active_days'] }}</strong><small>Días activos</small></span><span class="vf-metric-note">Tu recorrido</span></div>
            <div><span class="vf-metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 8v8m4-11v14m10-14v14m4-11v8M7 12h10"/></svg></span><span><strong>{{ $experience['total_sets'] }}</strong><small>Series registradas</small></span></div>
            <div><span class="vf-metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4v10l-8 4-8-4V7l8-4Zm0 0v18M4 7l8 4 8-4"/></svg></span><span><strong>{{ count($experience['muscles']) }}</strong><small>Grupos trabajados</small></span></div>
        </section>

        @if($isDemo)
            <section id="vf-demo-lab" class="vf-panel vf-demo-lab" aria-labelledby="vf-lab-heading">
                <div><p class="vf-eyebrow">Probalo en vivo <span class="vf-demo-label vf-demo-label--inline">Demo</span></p><h2 id="vf-lab-heading">Probá registrar tu entrenamiento.</h2><p>Elegí un ejercicio en el mapa y simulá una serie. Esta prueba no modifica tus datos.</p></div>
                <div class="vf-demo-controls"><a href="#vf-avatar-map" class="vf-text-link">Volver al mapa muscular <span aria-hidden="true">↑</span></a><button type="button" class="vf-secondary-button" @click="simulateSet()" :disabled="simulatedSets >= demoSetLimit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12 4 4 8-8"/></svg><span x-text="simulatedSets >= demoSetLimit ? 'Prueba completada' : 'Simular una serie'">Simular una serie</span><span class="vf-mono" x-text="simulatedSets + '/' + demoSetLimit">0/3</span></button><p class="vf-demo-feedback" aria-live="polite" x-text="demoStatus || 'Hasta 3 series de prueba. Sin XP real.'">Hasta 3 series de prueba. Sin XP real.</p></div>
            </section>
        @endif

        <div class="vf-bottom-grid">
            <section class="vf-panel vf-achievements-preview" aria-labelledby="vf-achievement-heading"><div class="vf-section-heading"><div><p class="vf-eyebrow">Pequeños hitos. Gran recorrido.</p><h2 id="vf-achievement-heading">Tus logros</h2></div><span class="vf-count-badge">{{ $unlockedAchievements }} / {{ count($experience['achievements']) }}</span></div>
                <div class="vf-achievement-preview-list">@foreach(array_slice($experience['achievements'], 0, 3) as $achievement)<div class="vf-achievement-mini {{ $achievement['unlocked'] ? 'is-unlocked' : '' }}"><span class="vf-achievement-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 3 6 6 1-4 5 1 6-6-3-6 3 1-6-4-5 6-1Z"/></svg></span><div><strong>{{ $achievement['title'] }}</strong><span>{{ $achievement['unlocked'] ? 'Desbloqueado' : min($achievement['current'], $achievement['target']).' / '.$achievement['target'] }}</span></div>@if($achievement['unlocked'])<svg class="vf-check-icon" viewBox="0 0 24 24" aria-label="Desbloqueado"><path d="m6 12 4 4 8-8"/></svg>@endif</div>@endforeach</div>
                <a href="{{ $characterUrl }}" class="vf-text-link">Ver todos mis logros <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>
            </section>

            <section class="vf-panel vf-recent-card" aria-labelledby="vf-recent-heading"><div class="vf-section-heading"><div><p class="vf-eyebrow">{{ $isDemo ? 'Historial de ejemplo' : 'Actividad registrada' }}</p><h2 id="vf-recent-heading">Lo último que hiciste</h2></div><span class="vf-history-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11a9 9 0 1 1 3 8M3 4v7h7m2-4v5l3 2"/></svg></span></div>
                @forelse(array_slice($experience['recent_exercises'], 0, 3) as $entry)
                    <div class="vf-history-row"><span class="vf-history-dot" aria-hidden="true"></span><div><strong>{{ $entry['title'] }}</strong><span>{{ \Illuminate\Support\Carbon::parse($entry['logged_at'])->format('d/m · H:i') }}</span></div><span class="vf-set-result">{{ $entry['weight'] !== null ? rtrim(rtrim(number_format((float) $entry['weight'], 2, ',', ''), '0'), ',').' kg' : 'Corporal' }}<small>{{ $entry['reps'] }} reps</small></span></div>
                @empty
                    <div class="vf-empty-state"><span aria-hidden="true">↗</span><h3>Tu recorrido empieza con una serie.</h3><p>Cuando registres tu entrenamiento, lo vas a ver acá.</p><a href="{{ $trainingUrl }}" class="vf-text-link">Ver mi rutina <span aria-hidden="true">→</span></a></div>
                @endforelse
                @if(count($experience['recent_exercises']))<a href="{{ $progressUrl }}" class="vf-text-link">Ver mi progreso <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>@endif
            </section>
        </div>
        <p class="vf-page-note"><span class="vf-live-dot" aria-hidden="true"></span>Tu personaje refleja actividad registrada. No estima cambios físicos ni recuperación.</p>
    </main>
</x-layouts.experience>
