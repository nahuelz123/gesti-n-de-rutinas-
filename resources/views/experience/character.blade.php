@php
    $levelPercent = max(0, min(100, (int) $experience['level_percent']));
    $unlockedAchievements = collect($experience['achievements'])->where('unlocked', true)->count();
    $maximumMuscleSets = max([1, ...array_column($experience['muscle_activity'], 'sets')]);
    $avatarOptions = ['generic' => ['Genérica', 'Diseño esencial'], 'male' => ['Masculina', 'Marco amplio'], 'female' => ['Femenina', 'Marco estilizado']];
    $themeOptions = ['ion' => ['Ion', 'Cian eléctrico', 1], 'solar' => ['Solar', 'Lima radiante', 3], 'nova' => ['Nova', 'Violeta orbital', 5]];
@endphp

<x-layouts.experience :is-demo="$isDemo" :display-name="$displayName">
    <div class="vf-experience vf-character-page" x-data="visionFitExperience({ storageKey: @js($avatarStorageKey), level: @js($experience['level']), isDemo: @js($isDemo), initialMuscleMap: @js($initialMuscleMap), demoExercises: @js($demoExercises) })" :data-theme="theme">
        @if($isDemo)
            <div class="vf-demo-strip"><span class="vf-demo-label"><i aria-hidden="true"></i> Modo demo</span><span>Personaje e historial ficticios. La personalización es local.</span></div>
        @endif

        <header class="vf-page-header"><div><p class="vf-eyebrow">Mi personaje <span class="vf-header-slash" aria-hidden="true">/</span> {{ $displayName }}</p><h1>Tu recorrido. <span>Tu identidad.</span></h1><p class="vf-page-subtitle">Un personaje propio para acompañar cada paso.</p></div><a href="{{ $progressUrl }}" class="vf-text-link vf-header-link">Mi progreso <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg></a></header>

        <div class="vf-character-grid">
            <section class="vf-panel vf-avatar-card vf-character-avatar" aria-label="Tu personaje y nivel">
                <div class="vf-panel-heading"><div><p class="vf-eyebrow">{{ $isDemo ? 'Atleta de ejemplo' : 'Tu identidad en VisionFit' }}</p><h2>{{ $displayName }}</h2></div><span class="vf-level-chip"><span>NV</span> {{ str_pad((string) $experience['level'], 2, '0', STR_PAD_LEFT) }}</span></div>
                <x-client.muscle-avatar :primary="$initialMuscleMap['primary']" :secondary="$initialMuscleMap['secondary']" :label="$initialMuscleMap['label']" :available="$initialMuscleMap['available']" :inherit-state="true" />
                @if($isDemo)
                    <div class="vf-character-exercise"><label for="vf-character-exercise">Explorá el mapa</label><select id="vf-character-exercise" :value="selectedExerciseId" @change="selectExercise($event.target.value)">@foreach($demoExercises as $exercise)<option value="{{ $exercise['id'] }}">{{ $exercise['title'] }}</option>@endforeach</select></div>
                @endif
                <div class="vf-level-progress"><div class="vf-progress-heading"><strong>Nivel {{ $experience['level'] }}</strong><span class="vf-mono">{{ $experience['level_xp'] }} <span class="vf-muted">/ {{ $experience['next_level_xp'] }} XP</span></span></div><div class="vf-xp-track" role="progressbar" aria-label="Experiencia del nivel actual" aria-valuenow="{{ $experience['level_xp'] }}" aria-valuemin="0" aria-valuemax="{{ $experience['next_level_xp'] }}"><span style="width: {{ $levelPercent }}%"></span></div><p>{{ $experience['next_level_xp'] - $experience['level_xp'] }} XP para tu próximo nivel. Cada día activo suma 100.</p></div>
            </section>

            <div class="vf-customization-stack">
                <section class="vf-panel vf-customization-card" aria-labelledby="vf-customize-heading"><div class="vf-section-heading"><div><p class="vf-eyebrow">Hacelo tuyo</p><h2 id="vf-customize-heading">Elegí tu figura</h2></div><span class="vf-section-number" aria-hidden="true">01</span></div><p class="vf-card-description">La misma actividad, tu forma de representarla.</p>
                    <div class="vf-figure-options" role="group" aria-label="Figura del personaje">
                        @foreach($avatarOptions as $avatarId => [$avatarTitle, $avatarSubtitle])
                            <button type="button" class="vf-figure-option" @click="selectAvatar(@js($avatarId))" :aria-pressed="avatar === @js($avatarId)" :class="{ 'is-selected': avatar === @js($avatarId) }"><span class="vf-mini-figure vf-mini-figure--{{ $avatarId }}" aria-hidden="true"><svg viewBox="0 0 60 70"><path class="vf-mini-head" d="M22 14 30 11 38 14 40 25 35 34H25l-5-9Z"/><path class="vf-mini-body" d="m25 36-15 7-5 19h50l-5-19-15-7-5 8Z"/><path class="vf-mini-visor" d="m24 23 6 2 6-2"/></svg></span><strong>{{ $avatarTitle }}</strong><span>{{ $avatarSubtitle }}</span><i class="vf-option-indicator" aria-hidden="true"></i></button>
                        @endforeach
                    </div>
                </section>

                <section class="vf-panel vf-theme-card" aria-labelledby="vf-theme-heading"><div class="vf-section-heading"><div><p class="vf-eyebrow">Un nuevo color para cada etapa</p><h2 id="vf-theme-heading">Tu energía, tu estilo</h2></div><span class="vf-section-number" aria-hidden="true">02</span></div>
                    <div class="vf-theme-options" role="group" aria-label="Color del personaje">
                        @foreach($themeOptions as $themeId => [$themeTitle, $themeSubtitle, $requiredLevel])
                            <button type="button" class="vf-theme-option vf-theme-option--{{ $themeId }}" @click="selectTheme(@js($themeId))" :aria-pressed="theme === @js($themeId)" :class="{ 'is-selected': theme === @js($themeId) }" @disabled($experience['level'] < $requiredLevel)><span class="vf-theme-swatch" aria-hidden="true"><i></i></span><span><strong>{{ $themeTitle }}</strong><small>{{ $themeSubtitle }}</small></span><span class="vf-theme-availability">@if($experience['level'] < $requiredLevel)<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5Zm7 4v3"/></svg> Nivel {{ $requiredLevel }}@else<span class="vf-option-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"/></svg></span> Disponible
                            @endif</span></button>
                        @endforeach
                    </div>
                    <p class="vf-footnote vf-local-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v13H4Zm4 17h8m-4-4v4"/></svg>Tu elección se guarda en este navegador, para esta cuenta.</p>
                </section>

                <div class="vf-profile-stats" aria-label="Resumen de tu recorrido"><div><strong>{{ $experience['xp'] }}</strong><span>XP acumulados</span></div><div><strong>{{ $experience['active_weeks'] }}</strong><span>Semanas activas</span></div><div><strong>{{ $experience['active_days'] }}</strong><span>Días activos</span></div></div>
            </div>
        </div>

        <section class="vf-achievements-section" aria-labelledby="vf-all-achievements"><div class="vf-section-heading"><div><p class="vf-eyebrow">Reconocé tu constancia</p><h2 id="vf-all-achievements">Cada hito tiene su lugar.</h2></div><span class="vf-count-badge">{{ $unlockedAchievements }} / {{ count($experience['achievements']) }} logros</span></div>
            <div class="vf-achievement-grid">@foreach($experience['achievements'] as $achievement)<article class="vf-panel vf-achievement-card {{ $achievement['unlocked'] ? 'is-unlocked' : '' }}"><div class="vf-achievement-card-top"><span class="vf-achievement-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 3 6 6 1-4 5 1 6-6-3-6 3 1-6-4-5 6-1Z"/></svg></span><span class="vf-achievement-state">{{ $achievement['unlocked'] ? 'Desbloqueado' : 'En camino' }}</span></div><h3>{{ $achievement['title'] }}</h3><p>{{ $achievement['description'] }}</p><div class="vf-achievement-progress"><span>{{ min($achievement['current'], $achievement['target']) }} / {{ $achievement['target'] }}</span>@if($achievement['unlocked'])<svg class="vf-check-icon" viewBox="0 0 24 24" aria-label="Desbloqueado"><path d="m6 12 4 4 8-8"/></svg>@endif</div><div class="vf-achievement-track" role="progressbar" aria-label="{{ $achievement['title'] }}" aria-valuenow="{{ min($achievement['current'], $achievement['target']) }}" aria-valuemin="0" aria-valuemax="{{ $achievement['target'] }}"><span style="width: {{ $achievement['target'] > 0 ? min(100, round($achievement['current'] / $achievement['target'] * 100)) : 0 }}%"></span></div></article>@endforeach</div>
        </section>

        <div class="vf-bottom-grid vf-character-history">
            <section class="vf-panel" aria-labelledby="vf-muscle-history"><div class="vf-section-heading"><div><p class="vf-eyebrow">Mapa de actividad</p><h2 id="vf-muscle-history">Los grupos que trabajaste</h2></div><span class="vf-count-badge">{{ count($experience['muscles']) }} grupos</span></div>
                @forelse($experience['muscle_activity'] as $muscle)
                    <div class="vf-muscle-activity-row"><div><span>{{ $muscle['label'] }}</span><strong>{{ $muscle['sets'] }} <small>series</small></strong></div><div class="vf-muscle-activity-track"><span style="width: {{ round($muscle['sets'] / $maximumMuscleSets * 100) }}%"></span></div></div>
                @empty
                    <div class="vf-empty-state"><h3>Un mapa por descubrir.</h3><p>Cuando registres ejercicios con grupos conocidos, vas a verlos acá.</p></div>
                @endforelse
                <p class="vf-footnote">Una serie puede involucrar más de un grupo muscular. Esto no mide desarrollo físico.</p>
            </section>

            <section class="vf-panel vf-recent-card" aria-labelledby="vf-character-history"><div class="vf-section-heading"><div><p class="vf-eyebrow">{{ $isDemo ? 'Datos ficticios' : 'Tus registros' }}</p><h2 id="vf-character-history">Historial de entrenamiento</h2></div><span class="vf-history-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11a9 9 0 1 1 3 8M3 4v7h7m2-4v5l3 2"/></svg></span></div>
                @forelse($experience['recent_exercises'] as $entry)
                    <div class="vf-history-row"><span class="vf-history-dot" aria-hidden="true"></span><div><strong>{{ $entry['title'] }}</strong><span>{{ \Illuminate\Support\Carbon::parse($entry['logged_at'])->format('d/m · H:i') }}</span></div><span class="vf-set-result">{{ $entry['weight'] !== null ? rtrim(rtrim(number_format((float) $entry['weight'], 2, ',', ''), '0'), ',').' kg' : 'Corporal' }}<small>{{ $entry['reps'] }} reps</small></span></div>
                @empty
                    <div class="vf-empty-state"><h3>Tu primera serie abre el camino.</h3><p>El historial aparece cuando registrás tu actividad.</p><a href="{{ $trainingUrl }}" class="vf-text-link">Ir a mi rutina <span aria-hidden="true">→</span></a></div>
                @endforelse
                @if(count($experience['recent_exercises']))<a href="{{ $progressUrl }}" class="vf-text-link">Explorar mi progreso <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>@endif
            </section>
        </div>
        <p class="vf-page-note"><span class="vf-live-dot" aria-hidden="true"></span>Descansar también es parte del proceso. No perdés XP ni logros por hacerlo.</p>
    </div>
</x-layouts.experience>
