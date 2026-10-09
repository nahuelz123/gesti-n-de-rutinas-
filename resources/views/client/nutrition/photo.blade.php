<x-layouts.client>
<style>
    .photo-meal { max-width:650px; margin:0 auto; padding:24px 16px 110px; }
    .photo-meal h1 { font-size:27px; line-height:1.15; margin:14px 0 8px; }
    .photo-meal p { color:var(--clr-text-muted); font-size:14px; line-height:1.5; }
    .photo-meal-card { background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:18px; padding:20px; margin-top:22px; }
    .photo-meal label { display:block; margin:14px 0 6px; font-size:13px; font-weight:700; }
    .photo-meal input[type=file], .photo-meal input:not([type=checkbox]) { display:block; width:100%; box-sizing:border-box; min-height:46px; border:1px solid #444; border-radius:10px; background:#080808; color:#fff; padding:10px 12px; font:inherit; }
    .photo-meal input:focus { outline:2px solid var(--clr-primary); outline-offset:2px; }
    .photo-meal-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 12px; }
    .photo-meal button { width:100%; min-height:50px; margin-top:18px; border:0; border-radius:12px; background:var(--clr-primary); color:#fff; font:inherit; font-weight:800; cursor:pointer; }
    .photo-meal small { display:block; margin-top:8px; color:var(--clr-text-muted); line-height:1.5; }
    .photo-meal-error { color:#ff8c95; margin-top:10px; }
</style>
<div class="photo-meal">
    <a class="back-link" href="{{ route('client.nutrition.index') }}">← Mi alimentación</a>
    <h1>Registrar comida con foto</h1>
    <p>La IA estima lo que se ve. Revisá los alimentos, el tamaño de la porción y las calorías antes de sumarlos a tu diario. El aceite, las salsas y otros ingredientes pueden no verse.</p>

    <form class="photo-meal-card" method="POST" action="{{ route('client.nutrition.photo.analyze') }}" enctype="multipart/form-data">
        @csrf
        <label for="meal-photo">Foto de la comida</label>
        <input id="meal-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" required>
        <small>JPG, PNG o WebP, hasta 5 MB. La imagen se envía a {{ app(\App\Services\FileAiReader::class)->providerName() }} para el análisis y no se guarda en VisionFit.</small>
        <label style="display:flex; align-items:flex-start; gap:10px; font-weight:500;">
            <input type="checkbox" name="processing_consent" value="1" required style="margin-top:3px;">
            Autorizo el análisis de esta imagen con {{ app(\App\Services\FileAiReader::class)->providerName() }}.
        </label>
        @error('photo') <div class="photo-meal-error" role="alert">{{ $message }}</div> @enderror
        @error('processing_consent') <div class="photo-meal-error" role="alert">{{ $message }}</div> @enderror
        <button type="submit">Analizar foto</button>
    </form>

    @if ($estimate)
        <form class="photo-meal-card" method="POST" action="{{ route('client.nutrition.photo.confirm') }}">
            @csrf
            <h2 style="font-size:20px;">Revisá la estimación</h2>
            <p>Todos los valores son aproximados para la porción completa. Corregilos antes de guardar.</p>
            @if ($estimate['note'])
                <p style="margin-top:10px; color:#facc15;">{{ $estimate['note'] }}</p>
            @endif
            <label for="meal-name">Comida detectada</label>
            <input id="meal-name" name="name" value="{{ old('name', $estimate['name']) }}" maxlength="120" required>
            <div class="photo-meal-fields">
                @foreach (['quantity_grams' => 'Porción (g)', 'calories' => 'Calorías (kcal)', 'protein' => 'Proteína (g)', 'carbs' => 'Carbohidratos (g)', 'fat' => 'Grasas (g)'] as $field => $label)
                    <div>
                        <label for="meal-{{ $field }}">{{ $label }}</label>
                        <input id="meal-{{ $field }}" name="{{ $field }}" type="number" inputmode="decimal" min="{{ in_array($field, ['quantity_grams', 'calories']) ? 1 : 0 }}" max="{{ in_array($field, ['quantity_grams', 'calories']) ? 5000 : 500 }}" step="0.1" value="{{ old($field, $estimate[$field]) }}" required>
                        @error($field) <div class="photo-meal-error" role="alert">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>
            @error('name') <div class="photo-meal-error" role="alert">{{ $message }}</div> @enderror
            <button type="submit">Confirmar y guardar comida</button>
        </form>
    @endif
</div>
</x-layouts.client>
