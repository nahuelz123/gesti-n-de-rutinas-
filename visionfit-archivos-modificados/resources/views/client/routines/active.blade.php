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
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>
    @if (session('success'))
        <div class="alert-ok">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-ok">⚠️ {{ $errors->first() }}</div>
    @endif

    <p class="pg-label">Mi entrenamiento</p>
    <h1 class="pg-title">Rutina activa</h1>

    @if (!$assignment)
        <div class="empty">No hay rutina activa asignada.</div>
    @else

        <div class="routine-card">
            <div class="routine-card-name">{{ $assignment->routine->title }}</div>
            @if ($assignment->routine->description)
                <div class="routine-card-desc">{{ $assignment->routine->description }}</div>
            @endif
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

                                {{-- GIF --}}
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

                       @php
                     $logs = \App\Models\ExerciseLog::query()
                     ->whereHas('assignment', fn($q) => $q->where('client_id', $assignment->client_id))
                     ->where('routine_day_exercise_id', $dx->id)
                     ->whereDate('logged_at', today())
                     ->orderByDesc('logged_at')
                     ->get();
                     $nextSetNumber = $logs->max('set_number') + 1;
                       @endphp

                        <form method="POST" action="{{ route('client.logs.store') }}" class="log-form">
                            @csrf
                            <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
                            <input type="hidden" name="routine_day_exercise_id" value="{{ $dx->id }}">
                            <div class="lf-field">
                                <label>Serie</label>
                                <input name="set_number" value="{{ $nextSetNumber }}" required type="number" min="1">
                            </div>
                            <div class="lf-field" style="flex:1.4">
                                <label>Peso (kg)</label>
                                <input name="weight" placeholder="80" type="number" step="0.5">
                            </div>
                            <div class="lf-field">
                                <label>Reps</label>
                                <input name="reps" placeholder="8" type="number" min="1">
                            </div>
                            <button class="lf-btn" type="submit">Guardar</button>
                        </form>

                        @if ($logs->count())
                            <div class="logs-wrap">
                                <div class="logs-label">Series de hoy</div>
                                @foreach ($logs as $log)
                                    <div class="log-row">
                                        <div class="log-row-main">
                                            <span class="log-time">{{ $log->logged_at->format('d/m H:i') }}</span>
                                            <span class="log-set">Serie {{ $log->set_number }}</span>
                                            <span class="log-kg">{{ $log->weight ?? '—' }} kg</span>
                                            <span class="log-reps">{{ $log->reps ?? '—' }} reps</span>
                                        </div>
                                        <div class="log-row-actions">
                                            <button type="button" class="log-action-btn" onclick="toggleLogEdit({{ $log->id }})" title="Editar">✏️</button>
                                            <form method="POST" action="{{ route('client.logs.destroy', $log) }}" onsubmit="return confirm('¿Borrar esta serie?');" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="log-action-btn" title="Borrar">🗑️</button>
                                            </form>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('client.logs.update', $log) }}" class="log-edit-row" id="log-edit-{{ $log->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="set_number" value="{{ $log->set_number }}" min="1" placeholder="Serie" title="Serie">
                                        <input type="number" name="weight" value="{{ $log->weight }}" step="0.5" min="0" placeholder="Kg" title="Peso (kg)">
                                        <input type="number" name="reps" value="{{ $log->reps }}" min="1" placeholder="Reps" title="Reps">
                                        <button type="submit" class="log-edit-save">Guardar</button>
                                        <button type="button" class="log-edit-cancel" onclick="toggleLogEdit({{ $log->id }})">Cancelar</button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
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

function toggleLogEdit(logId) {
    const row = document.getElementById('log-edit-' + logId);
    if (row) row.classList.toggle('open');
}
</script>

</x-layouts.client>
