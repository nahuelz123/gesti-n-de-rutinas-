<x-layouts.client>

{{-- Video modal --}}
<div class="modal-overlay" id="videoModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title" id="modalTitle"></span>
            <button class="modal-close" onclick="closeVideo()">✕</button>
        </div>
        <div class="modal-body">
            <iframe id="modalIframe" allowfullscreen></iframe>
        </div>
    </div>
</div>

<div class="rw">
    <a class="back-link" href="{{ route('client.recipes.index') }}">← Catálogo de recetas</a>

    @if ($recipe->photo_url)
        <img src="{{ $recipe->photo_url }}" alt="{{ $recipe->title }}" style="width:100%; height:200px; object-fit:cover; border-radius:18px; margin-bottom:16px;">
    @endif

    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
        <div>
            <p class="pg-label">{{ \App\Services\NutritionCalculator::$mealTypeLabels[$recipe->meal_type] ?? 'Receta' }}</p>
            <h1 class="pg-title" style="font-size:30px; margin-bottom:16px;">{{ $recipe->title }}</h1>
        </div>
        @if ($recipe->video_url)
            <button class="btn-video" style="flex-shrink:0; margin-top:4px;"
                onclick="openVideo('{{ $recipe->video_url }}', '{{ addslashes($recipe->title) }}')">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M8 5v14l11-7z"/></svg>
                Ver tutorial
            </button>
        @endif
    </div>

    @if ($recipe->description)
        <p style="color:#999; font-size:14px; line-height:1.5; margin-bottom:20px;">{{ $recipe->description }}</p>
    @endif

    {{-- Macros --}}
    <div class="day-summary-card">
        <div class="day-summary-item">
            <div class="day-summary-value">{{ $recipe->calories ?? '-' }}</div>
            <div class="day-summary-label">Kcal</div>
        </div>
        <div class="day-summary-item">
            <div class="day-summary-value">{{ $recipe->protein ?? '-' }}g</div>
            <div class="day-summary-label">Proteína</div>
        </div>
        <div class="day-summary-item">
            <div class="day-summary-value">{{ $recipe->carbs ?? '-' }}g</div>
            <div class="day-summary-label">Carbos</div>
        </div>
        <div class="day-summary-item">
            <div class="day-summary-value">{{ $recipe->fat ?? '-' }}g</div>
            <div class="day-summary-label">Grasas</div>
        </div>
        @if ($recipe->prep_time)
            <div class="day-summary-item">
                <div class="day-summary-value">{{ $recipe->prep_time }}'</div>
                <div class="day-summary-label">Prep.</div>
            </div>
        @endif
    </div>

    {{-- Ingredientes --}}
    @if ($recipe->ingredients->isNotEmpty())
        <div class="section">
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title">Ingredientes ({{ $recipe->servings }} {{ $recipe->servings == 1 ? 'porción' : 'porciones' }})</span>
                </div>
                @foreach ($recipe->ingredients as $ing)
                    <div style="padding:12px 20px; border-bottom:1px solid #1a1a1a; font-size:14px; display:flex; justify-content:space-between;">
                        <span>{{ $ing->name }}</span>
                        @if ($ing->quantity)
                            <span style="color:#666;">{{ rtrim(rtrim(number_format($ing->quantity, 2), '0'), '.') }} {{ $ing->unit }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Preparación --}}
    @if ($recipe->instructions->isNotEmpty())
        <div class="section">
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title">Preparación</span>
                </div>
                @foreach ($recipe->instructions as $step)
                    <div style="padding:14px 20px; border-bottom:1px solid #1a1a1a; display:flex; gap:12px;">
                        <div style="width:24px; height:24px; border-radius:100px; background:rgba(230,57,70,0.12); color:#e63946; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            {{ $step->step }}
                        </div>
                        <div style="font-size:13px; color:#ccc; line-height:1.5;">{{ $step->instruction }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
function getYoutubeId(url) {
    const match = url.match(/(?:v=|youtu\.be\/)([^&?\/]+)/);
    return match ? match[1] : null;
}
function openVideo(url, title) {
    const id = getYoutubeId(url);
    if (!id) return;
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalIframe').src = 'https://www.youtube.com/embed/' + id + '?autoplay=1';
    document.getElementById('videoModal').classList.add('open');
}
function closeVideo() {
    document.getElementById('modalIframe').src = '';
    document.getElementById('videoModal').classList.remove('open');
}
document.getElementById('videoModal').addEventListener('click', function(e) {
    if (e.target === this) closeVideo();
});
</script>

</x-layouts.client>
