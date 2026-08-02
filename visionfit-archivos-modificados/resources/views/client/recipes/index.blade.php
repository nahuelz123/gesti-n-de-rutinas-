<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    <p class="pg-label">Ideas de comida</p>
    <h1 class="pg-title">Catálogo de recetas</h1>

    {{-- Filtro por tipo de comida --}}
    <div class="day-tabs">
        <a href="{{ route('client.recipes.index', array_filter(['sort' => $sort])) }}" class="day-tab {{ !$mealType ? 'active' : '' }}">Todas</a>
        @foreach ($mealTypeLabels as $key => $label)
            <a href="{{ route('client.recipes.index', array_filter(['meal_type' => $key, 'sort' => $sort])) }}" class="day-tab {{ $mealType === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- Orden --}}
    <div style="display:flex; gap:8px; margin-bottom:20px;">
        @foreach (['recientes' => 'Más recientes', 'proteina' => 'Más proteína', 'calorias' => 'Menos calorías'] as $key => $label)
            <a href="{{ route('client.recipes.index', array_filter(['meal_type' => $mealType, 'sort' => $key])) }}"
               style="font-size:11px; font-weight:700; letter-spacing:0.04em; text-decoration:none; padding:6px 12px; border-radius:100px; border:1px solid {{ $sort === $key ? '#e63946' : '#222' }}; color: {{ $sort === $key ? '#e63946' : '#666' }};">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @forelse ($recipes as $recipe)
        <a href="{{ route('client.recipes.show', $recipe) }}" class="meal-card" style="display:block; text-decoration:none; color:inherit; margin-bottom:12px;">
            <div class="meal-top">
                @if ($recipe->photo_url)
                    <img class="meal-photo" src="{{ $recipe->photo_url }}" alt="{{ $recipe->title }}" loading="lazy">
                @else
                    <div class="meal-photo-placeholder">🍽️</div>
                @endif

                <div class="meal-info">
                    <div class="meal-name">{{ $recipe->title }}</div>
                    <div class="meal-macros">
                        @if ($recipe->calories)<span class="meal-macro-item"><b>{{ $recipe->calories }}</b> kcal</span>@endif
                        @if ($recipe->protein)<span class="meal-macro-item">P <b>{{ $recipe->protein }}g</b></span>@endif
                        @if ($recipe->carbs)<span class="meal-macro-item">C <b>{{ $recipe->carbs }}g</b></span>@endif
                        @if ($recipe->fat)<span class="meal-macro-item">G <b>{{ $recipe->fat }}g</b></span>@endif
                    </div>
                    @if ($recipe->prep_time)
                        <div style="font-size:11px; color:#666; margin-top:6px;">⏱️ {{ $recipe->prep_time }} min</div>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="empty">No hay recetas cargadas todavía para este filtro.</div>
    @endforelse

    <div style="margin-top:20px;">
        {{ $recipes->onEachSide(1)->links('vendor.pagination.visionfit') }}
    </div>
</div>

</x-layouts.client>
