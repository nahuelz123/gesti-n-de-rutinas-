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

    <a class="back-link" href="{{ route('client.routines.history') }}">← Volver</a>

    <p class="pg-label">Detalle de rutina</p>
    <h1 class="pg-title">{{ $assignment->routine->title }}</h1>

    @if ($assignment->routine->description)
        <div class="routine-card">
            <div class="routine-card-desc">{{ $assignment->routine->description }}</div>
        </div>
    @endif

    <div class="routine-card" style="margin-bottom:12px;">
        <div class="routine-card-desc">
            Asignada el {{ $assignment->assigned_at?->format('d/m/Y') }} &nbsp;·&nbsp;
            <span class="status-badge {{ $assignment->status === 'active' ? 'status-active' : 'status-inactive' }}">
                {{ $assignment->status }}
            </span>
        </div>
    </div>

    @foreach ($assignment->routine->days as $day)
        <div class="day-block">
            <div class="day-header">
                <span class="day-badge">Día {{ $day->day_number }}</span>
                <span class="day-title">{{ $day->title }}</span>
            </div>

            @foreach ($day->exercises->sortBy('order') as $dx)
                <div class="ex-card">
                    <div class="ex-top">
                        <div class="ex-top-row">

                            @if ($dx->exercise->gif_url)
                                <img class="ex-gif"
                                     src="{{ $dx->exercise->gif_url }}"
                                     alt="{{ $dx->exercise->title }}"
                                     loading="lazy">
                            @else
                                <div class="ex-gif-placeholder">💪</div>
                            @endif

                            <div class="ex-info">
                                <div class="ex-row">
                                    <div class="ex-name">{{ $dx->exercise->title }}</div>
                                    <div class="ex-actions">
                                        @if ($dx->exercise->video_url)
                                            <button class="btn-video"
                                                onclick="openVideo('{{ $dx->exercise->video_url }}', '{{ addslashes($dx->exercise->title) }}')">
                                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M8 5v14l11-7z"/></svg>
                                                Video
                                            </button>
                                        @endif
                                        <a class="ex-link" href="{{ route('client.progress.exercise', $dx->exercise_id) }}">
                                            Progreso →
                                        </a>
                                    </div>
                                </div>
                                <div class="ex-meta">
                                    <span class="ex-meta-item">Series <b>{{ $dx->sets }}</b></span>
                                    <span class="ex-meta-item">Reps <b>{{ $dx->reps }}</b></span>
                                    @if ($dx->rest)
                                        <span class="ex-meta-item">Descanso <b>{{ $dx->rest }}</b></span>
                                    @endif
                                </div>
                                @if ($dx->notes)
                                    <div class="ex-notes">{{ $dx->notes }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

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
