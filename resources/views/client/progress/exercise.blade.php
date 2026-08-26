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

    <a class="back-link" href="{{ url()->previous() }}">← Volver</a>

    <p class="pg-label">Progreso</p>
    <h1 class="pg-title">{{ $exercise->title }}</h1>

    {{-- Botón video --}}
    @if ($exercise->video_url)
        <div style="margin-bottom:16px;">
            <button class="btn-video" style="font-size:11px; padding:6px 14px;"
                onclick="openVideo('{{ $exercise->video_url }}', '{{ addslashes($exercise->title) }}')">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;"><path d="M8 5v14l11-7z"/></svg>
                Ver tutorial
            </button>
        </div>
    @endif

    {{-- PR + último registro --}}
    <div class="pr-card">
        <div>
            <div class="pr-label">Récord personal</div>
            @if ($pr)
                <div class="pr-value">{{ $pr }}<span class="pr-unit">kg</span></div>
            @else
                <div class="pr-none">—</div>
            @endif
        </div>
        @if ($last)
            <div class="last-log">
                <div class="last-log-label">Último registro</div>
                <div class="last-log-date">{{ $last->logged_at->format('d/m/Y H:i') }}</div>
                <div class="last-log-detail">
                    Serie {{ $last->set_number }} &nbsp;·&nbsp;
                    {{ $last->weight ?? '—' }} kg &nbsp;·&nbsp;
                    {{ $last->reps ?? '—' }} reps
                </div>
            </div>
        @endif
    </div>

    {{-- Gráfico --}}
    @if (!$logs->isEmpty())
        <div class="chart-card">
            <div class="chart-card-header">
                <span class="chart-card-title">Evolución</span>
                <div class="chart-tabs">
                    <button class="chart-tab active" onclick="switchChart('weight', this)">Peso</button>
                    <button class="chart-tab" onclick="switchChart('reps', this)">Reps</button>
                </div>
            </div>
            <div class="chart-wrap">
                <canvas id="progressChart"></canvas>
            </div>
        </div>
    @endif

    {{-- Historial --}}
    <div class="logs-card">
        <div class="logs-card-header">
            <span class="logs-card-title">Historial de series</span>
        </div>
        @if ($logs->isEmpty())
            <div class="empty-text">Todavía no hay registros para este ejercicio.</div>
        @else
        <div x-data="{ openLog: false, log: {} }">
            @foreach ($logs as $log)
                <div class="log-row" style="cursor: pointer; position: relative;"
                     @click="openLog = true; log = { id: {{ $log->id }}, set_number: {{ $log->set_number }}, weight: '{{ $log->weight }}', reps: '{{ $log->reps }}', logged_at: '{{ $log->logged_at->format('Y-m-d\TH:i') }}' }">
                    <span class="log-time">{{ $log->logged_at->format('d/m H:i') }}</span>
                    <span class="log-set">Serie {{ $log->set_number }}</span>
                    <span class="log-kg">{{ $log->weight ?? '—' }} kg</span>
                    <span class="log-reps">{{ $log->reps ?? '—' }} reps</span>
                    <span style="opacity: 0.5; margin-left: auto;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path></svg>
                    </span>
                </div>
            @endforeach

            {{-- Modal Alpine para editar serie --}}
            <div class="modal-overlay" :class="{ 'open': openLog }" style="display:none;" x-show="openLog">
                <div class="modal-box" style="background: var(--clr-card); border: 1px solid var(--clr-border);" @click.away="openLog = false">
                    <div class="modal-header" style="border-bottom: 1px solid var(--clr-border);">
                        <span class="modal-title" style="color:var(--clr-text);">Editar Serie</span>
                        <button class="modal-close" type="button" @click="openLog = false" style="color:var(--clr-text-muted);">✕</button>
                    </div>
                    <div class="modal-body">
                        <form :action="`/app/logs/${log.id}`" method="POST">
                            @csrf
                            @method('PUT')
                            <div style="margin-bottom: 15px;">
                                <label style="display:block; font-size:12px; color:var(--clr-text-muted); margin-bottom:4px;">Serie N°</label>
                                <input type="number" name="set_number" x-model="log.set_number" required min="1" max="20" style="width:100%; background:var(--clr-bg); border:1px solid var(--clr-border); color:var(--clr-text); padding:8px; border-radius:6px;">
                            </div>
                            <div style="display:flex; gap:10px; margin-bottom:15px;">
                                <div style="flex:1;">
                                    <label style="display:block; font-size:12px; color:var(--clr-text-muted); margin-bottom:4px;">Peso (kg)</label>
                                    <input type="number" name="weight" x-model="log.weight" step="0.5" min="0" style="width:100%; background:var(--clr-bg); border:1px solid var(--clr-border); color:var(--clr-text); padding:8px; border-radius:6px;">
                                </div>
                                <div style="flex:1;">
                                    <label style="display:block; font-size:12px; color:var(--clr-text-muted); margin-bottom:4px;">Repeticiones</label>
                                    <input type="number" name="reps" x-model="log.reps" min="1" max="200" style="width:100%; background:var(--clr-bg); border:1px solid var(--clr-border); color:var(--clr-text); padding:8px; border-radius:6px;">
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display:block; font-size:12px; color:var(--clr-text-muted); margin-bottom:4px;">Fecha y Hora</label>
                                <input type="datetime-local" name="logged_at" x-model="log.logged_at" required style="width:100%; background:var(--clr-bg); border:1px solid var(--clr-border); color:var(--clr-text); padding:8px; border-radius:6px; color-scheme: dark;">
                            </div>
                            
                            <div style="display:flex; gap:10px; justify-content:space-between;">
                                <button type="button" @click="if(confirm('¿Eliminar esta serie?')) { document.getElementById('deleteForm' + log.id).submit(); }" style="background:transparent; color:#e63946; border:1px solid #e63946; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">
                                    Eliminar
                                </button>
                                <button type="submit" style="background:#e63946; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            @foreach ($logs as $log)
                <form id="deleteForm{{ $log->id }}" action="{{ route('client.logs.destroy', $log) }}" method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        </div>
        @endif
    </div>

</div>

@if (!$logs->isEmpty())
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
@php
    $chartData = $logs->sortBy('logged_at')->values()->map(function($l) {
        return [
            'date'   => $l->logged_at->format('d/m H:i'),
            'weight' => (float) ($l->weight ?? 0),
            'reps'   => (int)   ($l->reps   ?? 0),
        ];
    });
@endphp
const rawLogs  = {!! json_encode($chartData) !!};
const labels   = rawLogs.map(l => l.date);
const weights  = rawLogs.map(l => l.weight);
const repsData = rawLogs.map(l => l.reps);

const ctx = document.getElementById('progressChart').getContext('2d');

const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1f1f1f',
            borderColor: '#333',
            borderWidth: 1,
            titleColor: '#888',
            bodyColor: '#f0f0f0',
            titleFont: { family: 'Inter', size: 10, weight: '700' },
            bodyFont: { family: 'Inter', size: 13, weight: '700' },
            padding: 10,
        },
    },
    scales: {
        x: {
            ticks: { color: '#444', font: { family: 'Inter', size: 9 }, maxTicksLimit: 8, maxRotation: 0 },
            grid: { color: '#1e1e1e' },
            border: { color: '#222' },
        },
        y: {
            ticks: { color: '#444', font: { family: 'Inter', size: 10 } },
            grid: { color: '#1e1e1e' },
            border: { color: '#222' },
        },
    },
};

function makeDataset(data) {
    return {
        data,
        borderColor: '#e63946',
        backgroundColor: 'rgba(230,57,70,0.06)',
        pointBackgroundColor: '#e63946',
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2,
        fill: true,
        tension: 0.35,
    };
}

const chart = new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [makeDataset(weights)] },
    options: commonOptions,
});

function switchChart(type, btn) {
    document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    chart.data.datasets[0] = makeDataset(type === 'weight' ? weights : repsData);
    chart.update();
}
</script>
@endif

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
