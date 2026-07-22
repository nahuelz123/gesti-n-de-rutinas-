<x-layouts.client>

<div class="rw">

    <p class="pg-label">Bienvenido</p>
    <h1 class="pg-title">Mi panel</h1>

    {{-- Rutina activa --}}
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Rutina activa</span>
            </div>
            @if ($active)
                <div class="active-row">
                    <div style="flex:1; min-width:0;">
                        <div class="active-name">{{ $active->routine->title }}</div>
                        <div class="active-date">Asignada el {{ $active->assigned_at?->format('d/m/Y') }}</div>
                    </div>
                    <span class="active-badge">Activa</span>
                    <a class="card-link" href="{{ route('client.routines.active') }}">Ver →</a>
                </div>
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
                <span class="section-title">Plan de dieta</span>
                @if ($dietAssignment)
                    <a class="card-link" href="{{ route('client.nutrition.index') }}">Ver plan →</a>
                @endif
            </div>

            @if (!$dietAssignment)
                <div class="empty-text">No tenés un plan de dieta activo.</div>
            @else
                <div class="active-row">
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
                </div>

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
                <span class="section-title">Historial</span>
                <a class="card-link" href="{{ route('client.routines.history') }}">Ver todo →</a>
            </div>
            @forelse ($history as $a)
                <div class="history-row">
                    <div style="flex:1; min-width:0;">
                        <div class="history-name">{{ $a->routine->title }}</div>
                        <div class="history-meta">
                            <span class="history-date">{{ $a->assigned_at?->format('d/m/Y') }}</span>
                            <span class="status-badge {{ $a->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                {{ $a->status }}
                            </span>
                        </div>
                    </div>
                    <a class="card-link" href="{{ route('client.routines.show', $a) }}">Ver →</a>
                </div>
            @empty
                <div class="empty-text">Todavía no hay historial.</div>
            @endforelse
        </div>
    </div>

</div>

</x-layouts.client>