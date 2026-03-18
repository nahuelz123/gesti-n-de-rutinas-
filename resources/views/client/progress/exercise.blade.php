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

    <a class="back-link" href="{{ route('client.routines.active') }}">← Volver</a>

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
                    Set {{ $last->set_number }} &nbsp;·&nbsp;
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
            <span class="logs-card-title">Historial de sets</span>
        </div>
        @if ($logs->isEmpty())
            <div class="empty-text">Todavía no hay registros para este ejercicio.</div>
        @else
            @foreach ($logs as $log)
                <div class="log-row">
                    <span class="log-time">{{ $log->logged_at->format('d/m H:i') }}</span>
                    <span class="log-set">Set {{ $log->set_number }}</span>
                    <span class="log-kg">{{ $log->weight ?? '—' }} kg</span>
                    <span class="log-reps">{{ $log->reps ?? '—' }} reps</span>
                </div>
            @endforeach
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
            titleFont: { family: 'Montserrat', size: 10, weight: '700' },
            bodyFont: { family: 'Montserrat', size: 13, weight: '700' },
            padding: 10,
        },
    },
    scales: {
        x: {
            ticks: { color: '#444', font: { family: 'Montserrat', size: 9 }, maxTicksLimit: 8, maxRotation: 0 },
            grid: { color: '#1e1e1e' },
            border: { color: '#222' },
        },
        y: {
            ticks: { color: '#444', font: { family: 'Montserrat', size: 10 } },
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