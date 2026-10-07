<x-layouts.client>
<style>
    .vf-dashboard {
        max-width:1080px;
        margin:0 auto;
        padding:24px 16px 110px;
    }
    .vf-dashboard-header {
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:16px;
        margin-bottom:24px;
    }
    .vf-dashboard-kicker {
        color:var(--clr-primary);
        font-size:11px;
        font-weight:800;
        letter-spacing:.12em;
        text-transform:uppercase;
        margin-bottom:6px;
    }
    .vf-dashboard-title {
        margin:0;
        color:var(--clr-text);
        font-size:30px;
        line-height:1.05;
        font-weight:850;
        letter-spacing:-.04em;
    }
    .vf-dashboard-subtitle {
        margin-top:7px;
        color:var(--clr-text-muted);
        font-size:14px;
    }
    .vf-dashboard-grid {
        display:grid;
        grid-template-columns:minmax(0,1.45fr) minmax(280px,.85fr);
        gap:16px;
        align-items:start;
    }
    .vf-stack { display:flex; flex-direction:column; gap:16px; }
    .vf-panel {
        position:relative;
        overflow:hidden;
        background:linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.01)),var(--clr-surface);
        border:1px solid var(--clr-border);
        border-radius:20px;
        padding:20px;
    }
    .vf-training-panel {
        border-color:rgba(230,57,70,.32);
        background:
            radial-gradient(circle at 100% 0%,rgba(230,57,70,.16),transparent 35%),
            linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.01)),
            var(--clr-surface);
    }
    .vf-panel-label {
        color:var(--clr-text-muted);
        font-size:10px;
        font-weight:800;
        letter-spacing:.12em;
        text-transform:uppercase;
    }
    .vf-training-top {
        display:flex;
        justify-content:space-between;
        gap:14px;
        margin-top:10px;
    }
    .vf-training-title {
        color:var(--clr-text);
        font-size:24px;
        line-height:1.1;
        font-weight:800;
        letter-spacing:-.03em;
    }
    .vf-training-meta {
        color:var(--clr-text-muted);
        font-size:13px;
        margin-top:7px;
    }
    .vf-training-icon {
        flex:0 0 48px;
        width:48px;
        height:48px;
        border-radius:15px;
        display:grid;
        place-items:center;
        background:rgba(230,57,70,.12);
        color:var(--clr-primary);
        border:1px solid rgba(230,57,70,.18);
    }
    .vf-training-summary {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:8px;
        margin-top:18px;
    }
    .vf-stat {
        min-width:0;
        border:1px solid rgba(255,255,255,.07);
        border-radius:13px;
        background:rgba(0,0,0,.16);
        padding:11px;
    }
    .vf-stat-label {
        color:var(--clr-text-muted);
        font-size:9px;
        font-weight:800;
        letter-spacing:.08em;
        text-transform:uppercase;
    }
    .vf-stat-value {
        display:block;
        margin-top:5px;
        color:var(--clr-text);
        font-size:14px;
        font-weight:800;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .vf-week-row {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin-top:16px;
        color:var(--clr-text-muted);
        font-size:11px;
        font-weight:700;
    }
    .vf-week-track {
        width:100%;
        height:7px;
        border-radius:999px;
        background:rgba(255,255,255,.08);
        overflow:hidden;
        margin-top:8px;
    }
    .vf-week-fill {
        height:100%;
        border-radius:inherit;
        background:linear-gradient(90deg,var(--clr-primary),#fb7185);
        transition:width .25s ease;
    }
    .vf-dashboard .client-btn {
        min-height:50px;
        border-radius:13px;
        font-weight:800;
    }
    .vf-training-action { margin-top:18px; }
    .vf-section-title {
        color:var(--clr-text);
        font-size:16px;
        font-weight:800;
        letter-spacing:-.02em;
        margin-bottom:12px;
    }
    .vf-nutrition-head {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:16px;
        margin-top:12px;
        margin-bottom:16px;
    }
    .vf-kcal {
        color:var(--clr-text);
        font-size:34px;
        line-height:1;
        font-weight:850;
        letter-spacing:-.04em;
    }
    .vf-kcal small {
        display:block;
        margin-top:5px;
        color:var(--clr-text-muted);
        font-size:11px;
        font-weight:700;
        letter-spacing:.05em;
        text-transform:uppercase;
    }
    .vf-progress-track {
        width:100%;
        height:8px;
        border-radius:999px;
        background:var(--clr-surface-elevated);
        overflow:hidden;
        margin-bottom:16px;
    }
    .vf-progress-fill {
        height:100%;
        border-radius:inherit;
        background:var(--clr-primary);
    }
    .vf-macro-grid {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:8px;
        margin-bottom:16px;
    }
    .vf-macro {
        border:1px solid var(--clr-border);
        background:rgba(255,255,255,.025);
        border-radius:13px;
        padding:11px 8px;
        text-align:center;
    }
    .vf-macro span {
        display:block;
        color:var(--clr-text-muted);
        font-size:9px;
        font-weight:800;
        letter-spacing:.08em;
        text-transform:uppercase;
    }
    .vf-macro strong {
        display:block;
        margin-top:4px;
        color:var(--clr-text);
        font-size:15px;
    }
    .vf-quick-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }
    .vf-quick {
        text-decoration:none;
        color:var(--clr-text);
        border:1px solid var(--clr-border);
        background:rgba(255,255,255,.02);
        border-radius:16px;
        padding:14px;
        min-height:96px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        transition:transform .15s ease,border-color .15s ease,background .15s ease;
    }
    .vf-quick:hover {
        transform:translateY(-2px);
        border-color:#3a3a3a;
        background:rgba(255,255,255,.035);
    }
    .vf-quick-icon {
        width:34px;
        height:34px;
        border-radius:10px;
        display:grid;
        place-items:center;
        background:var(--clr-surface-elevated);
        color:var(--clr-text);
    }
    .vf-quick strong { font-size:13px; font-weight:750; }
    .vf-empty {
        color:var(--clr-text-muted);
        font-size:14px;
        line-height:1.5;
        margin:12px 0 18px;
    }
    @media (max-width:820px) {
        .vf-dashboard-grid { grid-template-columns:1fr; }
    }
    @media (max-width:520px) {
        .vf-dashboard { padding-top:20px; }
        .vf-dashboard-title { font-size:27px; }
        .vf-panel { padding:17px; border-radius:18px; }
        .vf-training-title { font-size:21px; }
        .vf-training-summary { grid-template-columns:1fr 1fr; }
        .vf-stat:first-child { grid-column:1 / -1; }
    }
</style>

<div class="vf-dashboard">
    <header class="vf-dashboard-header">
        <div>
            <div class="vf-dashboard-kicker">Tu día en VisionFit</div>
            <h1 class="vf-dashboard-title">Hola, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
            <p class="vf-dashboard-subtitle">Tu entrenamiento, nutrición y progreso en un solo lugar.</p>
        </div>
    </header>

    <div class="vf-dashboard-grid">
        <div class="vf-stack">
            <section class="vf-panel vf-training-panel">
                <div class="vf-panel-label">{{ $hasWorkoutToday ? 'Entrenamiento en curso' : 'Tu próximo entrenamiento' }}</div>

                @if ($active)
                    <div class="vf-training-top">
                        <div>
                            <h2 class="vf-training-title">{{ $active->routine->title }}</h2>
                            <p class="vf-training-meta">
                                {{ $active->routine->days->count() }} días · rutina activa
                            </p>
                        </div>
                        <div class="vf-training-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="vf-training-summary">
                        <div class="vf-stat">
                            <span class="vf-stat-label">Esta semana</span>
                            <strong class="vf-stat-value">{{ $weeklyCompletedSessions }}/{{ $weeklyTarget ?: $active->routine->days->count() }} sesiones</strong>
                        </div>
                        <div class="vf-stat">
                            <span class="vf-stat-label">{{ $hasWorkoutToday ? 'Entrenando' : 'Siguiente día' }}</span>
                            <strong class="vf-stat-value">{{ $nextWorkoutDay?->title ?? 'Elegí un día' }}</strong>
                        </div>
                        <div class="vf-stat">
                            <span class="vf-stat-label">Última sesión</span>
                            <strong class="vf-stat-value">{{ $lastWorkout?->logged_at ? $lastWorkout->logged_at->format('d/m') : 'Sin registros' }}</strong>
                        </div>
                    </div>

                    <div class="vf-week-row">
                        <span>Progreso semanal</span>
                        <span>{{ $weeklyProgressPct }}%</span>
                    </div>
                    <div class="vf-week-track" aria-label="Progreso de entrenamientos semanales">
                        <div class="vf-week-fill" style="width:{{ $weeklyProgressPct }}%"></div>
                    </div>

                    <div class="vf-training-action">
                        <a href="{{ route('client.routines.active') }}" style="text-decoration:none;">
                            <x-client.action-button variant="primary">
                                {{ $hasWorkoutToday ? 'CONTINUAR ENTRENAMIENTO' : 'EMPEZAR ENTRENAMIENTO' }}
                            </x-client.action-button>
                        </a>
                    </div>
                @else
                    <p class="vf-empty">Todavía no tenés una rutina activa asignada. Cuando tu coach te asigne una, aparecerá acá.</p>
                    <a href="{{ route('client.chat.index') }}" style="text-decoration:none;">
                        <x-client.action-button variant="secondary">Hablar con mi coach</x-client.action-button>
                    </a>
                @endif
            </section>

            <section class="vf-panel">
                <div class="vf-panel-label">Nutrición</div>

                @if ($dietAssignment && $todayDietDay)
                    @php
                        $caloriesTarget = $nutritionSummary['target']['calories'] ?? 0;
                        $proteinTarget = $nutritionSummary['target']['protein'] ?? 0;
                        $carbsTarget = $nutritionSummary['target']['carbs'] ?? 0;
                        $fatTarget = $nutritionSummary['target']['fat'] ?? 0;
                        $caloriesEaten = $nutritionSummary['eaten']['calories'] ?? 0;
                        $nutriPct = $caloriesTarget > 0 ? min(100, round(($caloriesEaten / $caloriesTarget) * 100)) : 0;
                    @endphp

                    <div class="vf-nutrition-head">
                        <div class="vf-kcal">
                            {{ round($caloriesEaten) }} / {{ round($caloriesTarget) }}
                            <small>kcal consumidas</small>
                        </div>
                        @if(($nutritionSummary['meals_total'] ?? 0) > 0)
                            <div style="text-align:right;">
                                <div style="font-size:18px;font-weight:800;color:var(--clr-success);">
                                    {{ $nutritionSummary['meals_done'] }}/{{ $nutritionSummary['meals_total'] }}
                                </div>
                                <div style="font-size:11px;color:var(--clr-text-muted);">comidas</div>
                            </div>
                        @endif
                    </div>

                    <div class="vf-progress-track" aria-label="Progreso calórico">
                        <div class="vf-progress-fill" style="width:{{ $nutriPct }}%"></div>
                    </div>

                    <div class="vf-macro-grid">
                        <div class="vf-macro">
                            <span>Proteína</span>
                            <strong>{{ round($proteinTarget) }} g</strong>
                        </div>
                        <div class="vf-macro">
                            <span>Carbos</span>
                            <strong>{{ round($carbsTarget) }} g</strong>
                        </div>
                        <div class="vf-macro">
                            <span>Grasas</span>
                            <strong>{{ round($fatTarget) }} g</strong>
                        </div>
                    </div>

                    <a href="{{ route('client.nutrition.index') }}" style="text-decoration:none;">
                        <x-client.action-button variant="secondary">VER MI PLAN</x-client.action-button>
                    </a>
                @else
                    <p class="vf-empty">No tenés un plan nutricional para hoy.</p>
                    <a href="{{ route('client.nutrition.index') }}" style="text-decoration:none;">
                        <x-client.action-button variant="secondary">Ver nutrición</x-client.action-button>
                    </a>
                @endif
            </section>
        </div>

        <aside class="vf-stack">
            <section class="vf-panel">
                <h2 class="vf-section-title">Accesos rápidos</h2>

                <div class="vf-quick-grid">
                    <a class="vf-quick" href="{{ route('client.progress.index') }}">
                        <span class="vf-quick-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m6 10V5m6 14v-7m4 7H2"/>
                            </svg>
                        </span>
                        <strong>Mi progreso</strong>
                    </a>

                    <a class="vf-quick" href="{{ route('client.recipes.index') }}">
                        <span class="vf-quick-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c4 0 7 2.2 7 5.2 0 1.5-.8 2.8-2 3.7V21H7v-9.1c-1.2-.9-2-2.2-2-3.7C5 5.2 8 3 12 3Z"/>
                            </svg>
                        </span>
                        <strong>Recetas</strong>
                    </a>

                    <a class="vf-quick" href="{{ route('client.chat.index') }}">
                        <span class="vf-quick-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 18 4 21v-5a8 8 0 1 1 4 2Z"/>
                            </svg>
                        </span>
                        <strong>Mi coach</strong>
                    </a>

                    <a class="vf-quick" href="{{ route('client.ai-chat.index') }}">
                        <span class="vf-quick-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.2 3.8L17 8l-3.8 1.2L12 13l-1.2-3.8L7 8l3.8-1.2L12 3Zm6 9 .8 2.2L21 15l-2.2.8L18 18l-.8-2.2L15 15l2.2-.8L18 12Z"/>
                            </svg>
                        </span>
                        <strong>Asistente IA</strong>
                    </a>
                </div>
            </section>
        </aside>
    </div>
</div>
</x-layouts.client>
