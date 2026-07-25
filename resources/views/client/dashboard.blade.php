<x-layouts.client>

<div class="rw">

    <div class="greeting-row">
        <div>
            <div class="greeting-title">Hola, {{ explode(' ', auth()->user()->name)[0] }} 👋</div>
            <div class="greeting-date">{{ now()->translatedFormat('l d \d\e F') }}</div>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div class="quick-actions">
        <a href="{{ route('client.progress.index') }}" class="quick-action">
            <div class="quick-action-icon" style="background:rgba(96,165,250,0.12); color:#60a5fa;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
            </div>
            <span class="quick-action-label">Progreso</span>
        </a>
        <a href="{{ route('client.recipes.index') }}" class="quick-action">
            <div class="quick-action-icon" style="background:rgba(74,222,128,0.12); color:#4ade80;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            </div>
            <span class="quick-action-label">Recetas</span>
        </a>
        <a href="{{ route('client.chat.index') }}" class="quick-action">
            <div class="quick-action-icon" style="background:rgba(230,57,70,0.12); color:#e63946;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-.72 0-1.437-.023-2.148-.069m8.148-6.573V6.75c0-1.108-.806-2.057-1.907-2.185a48.507 48.507 0 0 0-11.186 0c-1.1.128-1.907 1.077-1.907 2.185v4.286c0 1.108.806 2.057 1.907 2.185.106.012.213.023.32.033m9.166-4.5H3.75"/></svg>
            </div>
            <span class="quick-action-label">Coach</span>
        </a>
        <a href="{{ route('client.ai-chat.index') }}" class="quick-action">
            <div class="quick-action-icon" style="background:rgba(192,132,252,0.12); color:#c084fc;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
            </div>
            <span class="quick-action-label">Asistente IA</span>
        </a>
    </div>

    {{-- Rutina activa --}}
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title-row">
                    <span class="section-icon" style="background:rgba(230,57,70,0.12); color:#e63946;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
                    </span>
                    <span class="section-title">Rutina activa</span>
                </span>
            </div>
            @if ($active)
                <a class="active-row" href="{{ route('client.routines.active') }}">
                    <div style="flex:1; min-width:0;">
                        <div class="active-name">{{ $active->routine->title }}</div>
                        <div class="active-date">Asignada el {{ $active->assigned_at?->format('d/m/Y') }}</div>
                    </div>
                    <span class="active-badge">Activa</span>
                    <span class="card-link">Ver →</span>
                </a>
            @else
                <div class="empty-text">No tenés una rutina activa.</div>
            @endif
        </div>
    </div>

    {{-- Plan de dieta activo --}}
    @php
        $caloriesTarget = $nutritionSummary['target']['calories'] ?? 0;
        $caloriesEaten = $nutritionSummary['eaten']['calories'] ?? 0;
        $nutriPct = $caloriesTarget > 0 ? min(100, round($caloriesEaten / $caloriesTarget * 100)) : 0;
        $nutriCircumference = 2 * M_PI * 48;
        $nutriOffset = $nutriCircumference - ($nutriPct / 100) * $nutriCircumference;
    @endphp
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title-row">
                    <span class="section-icon" style="background:rgba(74,222,128,0.12); color:#4ade80;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    </span>
                    <span class="section-title">Plan de dieta</span>
                </span>
                @if ($dietAssignment)
                    <a class="card-link" href="{{ route('client.nutrition.index') }}">Ver plan →</a>
                @endif
            </div>

            @if (!$dietAssignment)
                <div class="empty-text">No tenés un plan de dieta activo.</div>
            @else
                <a class="active-row" href="{{ route('client.nutrition.index') }}">
                    <div style="flex:1; min-width:0;">
                        <div class="active-name">{{ $dietAssignment->dietPlan->title }}</div>
                        <div class="active-date">
                            {{ $goalLabels[$dietAssignment->dietPlan->goal] ?? 'Sin objetivo definido' }}
                            @if ($dietAssignment->dietPlan->target_calories)
                                · {{ $dietAssignment->dietPlan->target_calories }} kcal/día objetivo
                            @endif
                        </div>
                    </div>
                    <span class="active-badge">Activo</span>
                </a>

                @if (!$todayDietDay)
                    <div class="empty-text">Hoy no tenés comidas planificadas en tu plan.</div>
                @else
                    <div class="nutri-ring-row">
                        <div class="nutri-ring">
                            <svg viewBox="0 0 108 108">
                                <circle class="nutri-ring-bg" cx="54" cy="54" r="48" />
                                <circle class="nutri-ring-fg" cx="54" cy="54" r="48"
                                    style="stroke-dasharray: {{ $nutriCircumference }}; stroke-dashoffset: {{ $nutriOffset }};" />
                            </svg>
                            <div class="nutri-ring-center">
                                <span class="nutri-ring-pct">{{ $nutriPct }}%</span>
                                <span class="nutri-ring-label">hoy</span>
                            </div>
                        </div>

                        <div class="nutri-stats">
                            <div class="nutri-stat-row">
                                <span class="nutri-stat-label">Calorías</span>
                                <span class="nutri-stat-value">{{ round($caloriesEaten) }} / {{ round($caloriesTarget) }} kcal</span>
                            </div>
                            <div class="nutri-bar">
                                <div class="nutri-bar-fill" style="width: {{ $nutriPct }}%"></div>
                            </div>

                            <div class="nutri-macros">
                                <div class="nutri-macro">
                                    <span class="nutri-macro-label">Prote</span>
                                    <span class="nutri-macro-value">{{ round($nutritionSummary['eaten']['protein'], 1) }}g</span>
                                </div>
                                <div class="nutri-macro">
                                    <span class="nutri-macro-label">Carbs</span>
                                    <span class="nutri-macro-value">{{ round($nutritionSummary['eaten']['carbs'], 1) }}g</span>
                                </div>
                                <div class="nutri-macro">
                                    <span class="nutri-macro-label">Grasas</span>
                                    <span class="nutri-macro-value">{{ round($nutritionSummary['eaten']['fat'], 1) }}g</span>
                                </div>
                            </div>

                            <div class="nutri-meals-done">
                                {{ $nutritionSummary['meals_done'] }}/{{ $nutritionSummary['meals_total'] }} comidas registradas hoy
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Historial --}}
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title-row">
                    <span class="section-icon" style="background:rgba(96,165,250,0.12); color:#60a5fa;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                    <span class="section-title">Historial</span>
                </span>
                <a class="card-link" href="{{ route('client.routines.history') }}">Ver todo →</a>
            </div>
            @forelse ($history as $a)
                <a class="history-row" href="{{ route('client.routines.show', $a) }}">
                    <div style="flex:1; min-width:0;">
                        <div class="history-name">{{ $a->routine->title }}</div>
                        <div class="history-meta">
                            <span class="history-date">{{ $a->assigned_at?->format('d/m/Y') }}</span>
                            <span class="status-badge {{ $a->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                {{ $a->status }}
                            </span>
                        </div>
                    </div>
                    <span class="card-link">Ver →</span>
                </a>
            @empty
                <div class="empty-text">Todavía no hay historial.</div>
            @endforelse
        </div>
    </div>

</div>

</x-layouts.client>