<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    @if (session('success'))
        <div class="alert-ok">{{ session('success') }}</div>
    @endif

    <p class="pg-label">Mi alimentación</p>
    <h1 class="pg-title">Plan de nutrición</h1>

    <a href="{{ route('client.recipes.index') }}" class="card-link" style="display:inline-block; margin-bottom:20px;">🍽️ Ver catálogo de recetas →</a>

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

            @if ($isToday)
                {{-- Consumido hoy vs objetivo (incluye comidas del plan marcadas + diario libre) --}}
                <div class="day-summary-card" style="flex-direction:column; align-items:stretch;">
                    <div class="nutri-stat-row">
                        <span class="nutri-stat-label">Calorías consumidas hoy</span>
                        <span class="nutri-stat-value">{{ round($summary['eaten']['calories']) }} / {{ round($summary['target']['calories']) }} kcal</span>
                    </div>
                    @php
                        $calPct = $summary['target']['calories'] > 0
                            ? min(100, round(($summary['eaten']['calories'] / $summary['target']['calories']) * 100))
                            : 0;
                    @endphp
                    <div class="nutri-bar"><div class="nutri-bar-fill" style="width: {{ $calPct }}%;"></div></div>

                    <div class="nutri-macros">
                        <div class="nutri-macro">
                            <span class="nutri-macro-label">Proteína</span>
                            <span class="nutri-macro-value">{{ round($summary['eaten']['protein'], 1) }}g / {{ round($summary['target']['protein'], 1) }}g</span>
                        </div>
                        <div class="nutri-macro">
                            <span class="nutri-macro-label">Carbos</span>
                            <span class="nutri-macro-value">{{ round($summary['eaten']['carbs'], 1) }}g / {{ round($summary['target']['carbs'], 1) }}g</span>
                        </div>
                        <div class="nutri-macro">
                            <span class="nutri-macro-label">Grasas</span>
                            <span class="nutri-macro-value">{{ round($summary['eaten']['fat'], 1) }}g / {{ round($summary['target']['fat'], 1) }}g</span>
                        </div>
                    </div>
                </div>
            @endif

            @foreach ($mealTypeOrder as $type)
                @if ($grouped->has($type))
                    @php $options = $grouped->get($type)->sortBy('order'); @endphp
                    <div class="meal-group">
                        <div class="meal-group-header">
                            <span class="day-badge">{{ $mealTypeLabels[$type] }}</span>
                            @if ($options->count() > 1)
                                <span style="font-size:11px; color:#666; margin-left:8px;">Elegí una opción</span>
                            @endif
                        </div>

                        @foreach ($options as $i => $dpr)
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
                                        <div class="meal-name">
                                            @if ($options->count() > 1)
                                                <span style="color:#666; font-size:11px; font-weight:700;">Opción {{ $i + 1 }} · </span>
                                            @endif
                                            @if ($recipe)
                                                <a href="{{ route('client.recipes.show', $recipe) }}" style="color:inherit; text-decoration:none;">{{ $recipe->title }} →</a>
                                            @else
                                                Receta eliminada
                                            @endif
                                        </div>
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
                                            ✓ Elegida
                                        @elseif ($isToday)
                                            {{ $options->count() > 1 ? 'Elegir esta opción' : 'Marcar como hecha' }}
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

    {{-- Diario libre: el alumno puede registrar cualquier comida por cantidad exacta,
         tenga o no un plan de dieta asignado. --}}
    <div class="free-log-section">
        <div class="free-log-header">
            <div>
                <div class="free-log-title">🍴 Diario libre de hoy</div>
                <div class="free-log-hint">Buscá el alimento, cargá la cantidad en gramos exacta y calculamos las calorías y macros solos.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('client.nutrition.free-logs.store') }}" class="free-log-form" id="free-log-form">
            @csrf
            <input type="hidden" name="food_item_id" id="food_item_id" value="">

            <div class="food-search-wrap">
                <input type="text" id="food-search-input" class="food-search-input" placeholder="🔎 Buscar alimento (ej: pechuga de pollo)..." autocomplete="off">
                <div class="food-search-results" id="food-search-results"></div>
            </div>

            <div class="free-log-selected" id="free-log-selected">
                <span id="free-log-selected-name"></span>
                <button type="button" id="free-log-clear">✕</button>
            </div>

            <span class="free-log-custom-toggle" id="free-log-custom-toggle">¿No lo encontrás? Cargalo a mano con sus macros por 100g →</span>

            <div class="free-log-custom-fields" id="free-log-custom-fields">
                <div>
                    <label>Nombre</label>
                    <input type="text" name="custom_name" id="custom_name" maxlength="120" placeholder="Ej: Milanesa casera">
                </div>
                <div>
                    <label>Kcal /100g</label>
                    <input type="number" step="0.1" name="custom_calories_per_100g" id="custom_calories_per_100g">
                </div>
                <div>
                    <label>Prot /100g</label>
                    <input type="number" step="0.1" name="custom_protein_per_100g" id="custom_protein_per_100g">
                </div>
                <div>
                    <label>Carb /100g</label>
                    <input type="number" step="0.1" name="custom_carbs_per_100g" id="custom_carbs_per_100g">
                </div>
            </div>

            <div class="free-log-row">
                <div class="free-log-qty-field">
                    <label>Cantidad (g)</label>
                    <input type="number" step="0.1" min="0.1" name="quantity_grams" id="quantity_grams" placeholder="Ej: 150">
                </div>
                <div class="free-log-preview" id="free-log-preview">Elegí un alimento y una cantidad para ver el cálculo.</div>
                <button type="submit" class="free-log-submit-btn" id="free-log-submit" disabled>Registrar</button>
            </div>
        </form>

        @forelse ($freeLogs as $log)
            <div class="free-log-list-item">
                <div>
                    <div class="free-log-list-name">{{ $log->displayName() }} · {{ rtrim(rtrim(number_format($log->quantity_grams, 1), '0'), '.') }}g</div>
                    <div class="free-log-list-macros">
                        {{ round($log->calories) }} kcal · P {{ round($log->protein, 1) }}g · C {{ round($log->carbs, 1) }}g · G {{ round($log->fat, 1) }}g
                    </div>
                </div>
                <form method="POST" action="{{ route('client.nutrition.free-logs.destroy', $log) }}" onsubmit="return confirm('¿Borrar este registro?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="free-log-delete-btn">🗑️</button>
                </form>
            </div>
        @empty
            <div class="empty" style="padding:24px;">Todavía no registraste nada hoy.</div>
        @endforelse

        @if ($freeLogs->isNotEmpty())
            <div class="day-summary-card" style="margin-top:10px;">
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($freeTotals['calories']) }}</div>
                    <div class="day-summary-label">Kcal (diario libre)</div>
                </div>
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($freeTotals['protein'], 1) }}g</div>
                    <div class="day-summary-label">Proteína</div>
                </div>
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($freeTotals['carbs'], 1) }}g</div>
                    <div class="day-summary-label">Carbos</div>
                </div>
                <div class="day-summary-item">
                    <div class="day-summary-value">{{ round($freeTotals['fat'], 1) }}g</div>
                    <div class="day-summary-label">Grasas</div>
                </div>
            </div>
        @endif
    </div>

    <script>
        (function () {
            const searchInput = document.getElementById('food-search-input');
            const resultsBox = document.getElementById('food-search-results');
            const foodItemIdField = document.getElementById('food_item_id');
            const selectedBox = document.getElementById('free-log-selected');
            const selectedName = document.getElementById('free-log-selected-name');
            const clearBtn = document.getElementById('free-log-clear');
            const customToggle = document.getElementById('free-log-custom-toggle');
            const customFields = document.getElementById('free-log-custom-fields');
            const qtyInput = document.getElementById('quantity_grams');
            const preview = document.getElementById('free-log-preview');
            const submitBtn = document.getElementById('free-log-submit');
            const nameInput = document.getElementById('custom_name');
            const calInput = document.getElementById('custom_calories_per_100g');
            const protInput = document.getElementById('custom_protein_per_100g');
            const carbInput = document.getElementById('custom_carbs_per_100g');

            let selectedFood = null; // {id, name, calories_per_100g, protein_per_100g, carbs_per_100g, fat_per_100g}
            let useCustom = false;
            let searchTimer = null;

            function round1(n) { return Math.round(n * 10) / 10; }

            function updatePreview() {
                const grams = parseFloat(qtyInput.value);
                let per100 = null;

                if (!useCustom && selectedFood) {
                    per100 = {
                        cal: parseFloat(selectedFood.calories_per_100g) || 0,
                        prot: parseFloat(selectedFood.protein_per_100g) || 0,
                        carb: parseFloat(selectedFood.carbs_per_100g) || 0,
                    };
                } else if (useCustom) {
                    per100 = {
                        cal: parseFloat(calInput.value) || 0,
                        prot: parseFloat(protInput.value) || 0,
                        carb: parseFloat(carbInput.value) || 0,
                    };
                }

                const ready = per100 && grams > 0 && (useCustom ? (nameInput.value.trim() !== '' && calInput.value !== '') : true);
                submitBtn.disabled = !ready;

                if (!per100 || !(grams > 0)) {
                    preview.textContent = 'Elegí un alimento y una cantidad para ver el cálculo.';
                    return;
                }

                const factor = grams / 100;
                preview.innerHTML = '<b>' + round1(per100.cal * factor) + ' kcal</b> · P ' + round1(per100.prot * factor) + 'g · C ' + round1(per100.carb * factor) + 'g';
            }

            function selectFood(food) {
                selectedFood = food;
                useCustom = false;
                foodItemIdField.value = food.id;
                selectedName.textContent = food.name;
                selectedBox.classList.add('visible');
                customFields.classList.remove('visible');
                searchInput.value = '';
                resultsBox.classList.remove('open');
                updatePreview();
            }

            clearBtn.addEventListener('click', function () {
                selectedFood = null;
                foodItemIdField.value = '';
                selectedBox.classList.remove('visible');
                updatePreview();
            });

            customToggle.addEventListener('click', function () {
                useCustom = !useCustom;
                customFields.classList.toggle('visible', useCustom);
                if (useCustom) {
                    selectedFood = null;
                    foodItemIdField.value = '';
                    selectedBox.classList.remove('visible');
                }
                updatePreview();
            });

            searchInput.addEventListener('input', function () {
                const q = searchInput.value.trim();
                clearTimeout(searchTimer);

                if (q.length < 2) {
                    resultsBox.classList.remove('open');
                    resultsBox.innerHTML = '';
                    return;
                }

                searchTimer = setTimeout(function () {
                    fetch('{{ route('client.nutrition.foods.search') }}?q=' + encodeURIComponent(q))
                        .then(function (r) { return r.json(); })
                        .then(function (items) {
                            resultsBox.innerHTML = '';

                            if (!items.length) {
                                resultsBox.innerHTML = '<div class="food-search-item" style="cursor:default;">Sin resultados. Podés cargarlo a mano abajo.</div>';
                                resultsBox.classList.add('open');
                                return;
                            }

                            items.forEach(function (food) {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'food-search-item';
                                btn.innerHTML = '<div class="food-search-item-name">' + food.name + '</div>'
                                    + '<div class="food-search-item-macros">' + food.calories_per_100g + ' kcal / 100g · P ' + food.protein_per_100g + 'g · C ' + food.carbs_per_100g + 'g · G ' + food.fat_per_100g + 'g</div>';
                                btn.addEventListener('click', function () { selectFood(food); });
                                resultsBox.appendChild(btn);
                            });

                            resultsBox.classList.add('open');
                        })
                        .catch(function () {});
                }, 250);
            });

            document.addEventListener('click', function (e) {
                if (!resultsBox.contains(e.target) && e.target !== searchInput) {
                    resultsBox.classList.remove('open');
                }
            });

            [qtyInput, calInput, protInput, carbInput, nameInput].forEach(function (el) {
                el.addEventListener('input', updatePreview);
            });
        })();
    </script>

</x-layouts.client>
