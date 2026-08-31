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
            @foreach ($logs as $log)
            <div x-data="{ editing: false }">
                {{-- Vista normal --}}
                <div class="log-row" x-show="!editing">
                    <span class="log-time">{{ $log->logged_at->format('d/m H:i') }}</span>
                    <span class="log-set">Serie {{ $log->set_number }}</span>
                    <span class="log-kg">{{ $log->weight ?? '—' }} kg</span>
                    <span class="log-reps">{{ $log->reps ?? '—' }} reps</span>
                    <span style="opacity:0.5;margin-left:auto;cursor:pointer;" @click="editing = true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path></svg>
                    </span>
                </div>

                {{-- Vista edición inline --}}
                <div x-show="editing" x-cloak style="padding:12px 16px;border-bottom:1px solid var(--clr-border);background:var(--clr-card);">
                    <form action="{{ route('client.logs.update', $log) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
                            <div style="display:flex;flex-direction:column;gap:3px;">
                                <label style="font-size:10px;color:var(--clr-text-muted);">Serie</label>
                                <input type="number" name="set_number" value="{{ $log->set_number }}" min="1" max="20" required
                                    style="width:60px;background:var(--clr-bg);border:1px solid var(--clr-border);color:var(--clr-text);padding:6px;border-radius:6px;font-size:13px;">
                            </div>
                            <div style="display:flex;flex-direction:column;gap:3px;">
                                <label style="font-size:10px;color:var(--clr-text-muted);">Peso (kg)</label>
                                <input type="number" name="weight" value="{{ $log->weight }}" step="0.5" min="0"
                                    style="width:80px;background:var(--clr-bg);border:1px solid var(--clr-border);color:var(--clr-text);padding:6px;border-radius:6px;font-size:13px;">
                            </div>
                            <div style="display:flex;flex-direction:column;gap:3px;">
                                <label style="font-size:10px;color:var(--clr-text-muted);">Reps</label>
                                <input type="number" name="reps" value="{{ $log->reps }}" min="1" max="200"
                                    style="width:60px;background:var(--clr-bg);border:1px solid var(--clr-border);color:var(--clr-text);padding:6px;border-radius:6px;font-size:13px;">
                            </div>
                        </div>
                        <input type="hidden" name="logged_at" value="{{ $log->logged_at->format('Y-m-d H:i:s') }}">
                        <div style="display:flex;gap:8px;">
                            <button type="button" @click="editing = false"
                                style="flex:1;background:transparent;color:var(--clr-text-muted);border:1px solid var(--clr-border);padding:7px;border-radius:6px;font-size:13px;cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="button" onclick="if(confirm('¿Eliminar?')) document.getElementById('del-{{ $log->id }}').submit()"
                                style="flex:1;background:transparent;color:#e63946;border:1px solid #e63946;padding:7px;border-radius:6px;font-size:13px;cursor:pointer;">
                                Eliminar
                            </button>
                            <button type="submit"
                                style="flex:1;background:#e63946;color:#fff;border:none;padding:7px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">
                                Guardar
                            </button>
                        </div>
                    </form>

                    <form id="del-{{ $log->id }}" action="{{ route('client.logs.destroy', $log) }}" method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
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
