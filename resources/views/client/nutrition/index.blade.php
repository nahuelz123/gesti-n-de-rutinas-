<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    @if (session('success'))
        <div class="alert-ok">{{ session('success') }}</div>
    @endif

    <p class="pg-label">Mi alimentación</p>
    <h1 class="pg-title">Plan de nutrición</h1>

    @if (!$assignment)
        <div class="empty">No tenés un plan de dieta activo.</div>
    @else
        <div class="day-summary-card" style="margin-bottom:14px;">
            <div style="flex:1; min-width:200px;">
                <div class="active-name">{{ $assignment->dietPlan->title }}</div>
                <div class="active-date">
                    {{ $goalLabels[$assignment->dietPlan->goal] ?? 'Sin objetivo definido' }}
                    @if ($assignment->dietPlan->target_calories)
                        · {{ $assignment->dietPlan->target_calories }} kcal/día objetivo
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs de días de la semana --}}
        <div class="day-tabs">
            @foreach ($dayMap as $iso => $key)
                <a href="{{ route('client.nutrition.index', ['day' => $key]) }}"
                   class="day-tab {{ $dayKey === $key ? 'active' : '' }}">
                    {{ $dayLabels[$key] }}
                </a>
            @endforeach
        </div>

        @if (!$day)
            <div class="empty">No hay comidas planificadas para {{ $dayLabels[$dayKey] }}.</div>
        @else
            {{-- Resumen del día --}}
            <div class="day-summary-card">
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($summary['target']['calories']) }}</div>
                    <div class="day-summary-label">Kcal objetivo</div>
                </div>
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($summary['target']['protein'], 1) }}g</div>
                    <div class="day-summary-label">Proteína</div>
                </div>
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($summary['target']['carbs'], 1) }}g</div>
                    <div class="day-summary-label">Carbos</div>
                </div>
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($summary['target']['fat'], 1) }}g</div>
                    <div class="day-summary-label">Grasas</div>
                </div>
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ $summary['meals_done'] }}/{{ $summary['meals_total'] }}</div>
                    <div class="day-summary-label">Registradas</div>
                </div>
            </div>

            @foreach ($mealTypeOrder as $type)
                @if ($grouped->has($type))
                    <div class="meal-group">
                        <div class="meal-group-header">
                            <span class="day-badge">{{ $mealTypeLabels[$type] }}</span>
                        </div>

                        @foreach ($grouped->get($type)->sortBy('order') as $dpr)
                            @php
                                $log = $summary['logs']->get($dpr->id);
                                $isDone = $log && $log->completed;
                                $recipe = $dpr->recipe;
                            @endphp

                            <div class="meal-card {{ $isDone ? 'done' : '' }}">
                                <div class="meal-top">
                                    @if ($recipe?->photo_url)
                                        <img class="meal-photo" src="{{ $recipe->photo_url }}" alt="{{ $recipe->title }}" loading="lazy">
                                    @else
                                        <div class="meal-photo-placeholder">🍽️</div>
                                    @endif

                                    <div class="meal-info">
                                        <div class="meal-name">{{ $recipe?->title ?? 'Receta eliminada' }}</div>
                                        @if ($recipe)
                                            <div class="meal-macros">
                                                <span class="meal-macro-item"><b>{{ round(($recipe->calories ?? 0) * $dpr->servings) }}</b> kcal</span>
                                                <span class="meal-macro-item">P <b>{{ round(($recipe->protein ?? 0) * $dpr->servings, 1) }}g</b></span>
                                                <span class="meal-macro-item">C <b>{{ round(($recipe->carbs ?? 0) * $dpr->servings, 1) }}g</b></span>
                                                <span class="meal-macro-item">G <b>{{ round(($recipe->fat ?? 0) * $dpr->servings, 1) }}g</b></span>
                                            </div>
                                        @endif
                                        @if ($dpr->notes)
                                            <div class="ex-notes">{{ $dpr->notes }}</div>
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('client.nutrition.logs.store') }}" class="meal-check-form">
                                    @csrf
                                    <input type="hidden" name="diet_assignment_id" value="{{ $assignment->id }}">
                                    <input type="hidden" name="diet_plan_day_recipe_id" value="{{ $dpr->id }}">
                                    <input type="hidden" name="completed" value="{{ $isDone ? 0 : 1 }}">
                                    <button type="submit"
                                        class="meal-check-btn {{ $isDone ? 'done' : '' }}"
                                        {{ $isToday ? '' : 'disabled' }}>
                                        @if ($isDone)
                                            ✓ Comida registrada
                                        @elseif ($isToday)
                                            Marcar como hecha
                                        @else
                                            Solo se registra en el día de hoy
                                        @endif
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
    @endif
</div>

</x-layouts.client>
