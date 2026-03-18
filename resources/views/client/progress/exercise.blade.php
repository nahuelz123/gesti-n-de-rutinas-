<x-layouts.client>
<style>
    .rw { max-width: 640px; margin: 0 auto; }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #555;
        text-decoration: none;
        margin-bottom: 14px;
        transition: color 0.15s;
    }
    .back-link:hover { color: #f0f0f0; }

    .pg-label {
        font-size: 10px;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #555;
        margin-bottom: 2px;
    }
    .pg-title {
        font-size: 34px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: -0.01em;
        line-height: 1.1;
        margin-bottom: 20px;
    }

    /* PR card */
    .pr-card {
        background: #161616;
        border: 1px solid #222;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .pr-label {
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #555;
        margin-bottom: 4px;
    }
    .pr-value {
        font-size: 36px;
        font-weight: 900;
        letter-spacing: -0.02em;
        color: #e8ff47;
        line-height: 1;
    }
    .pr-unit {
        font-size: 16px;
        font-weight: 600;
        color: #e8ff47;
        opacity: 0.7;
        margin-left: 3px;
    }
    .pr-none { font-size: 28px; font-weight: 900; color: #333; }

    .last-log {
        background: #1a1a1a;
        border: 1px solid #222;
        border-radius: 8px;
        padding: 10px 14px;
        flex-shrink: 0;
    }
    .last-log-label {
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: #444;
        margin-bottom: 5px;
    }
    .last-log-date { font-size: 11px; color: #555; margin-bottom: 4px; }
    .last-log-detail { font-size: 13px; font-weight: 700; color: #f0f0f0; }

    /* Chart card */
    .chart-card {
        background: #161616;
        border: 1px solid #222;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 14px;
    }
    .chart-card-header {
        padding: 12px 16px;
        border-bottom: 1px solid #1e1e1e;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chart-card-title {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #555;
    }
    .chart-tabs {
        display: flex;
        gap: 4px;
    }
    .chart-tab {
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 3px 9px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        background: transparent;
        color: #555;
        font-family: 'Montserrat', sans-serif;
    }
    .chart-tab.active {
        background: rgba(232,255,71,0.1);
        color: #e8ff47;
    }
    .chart-wrap {
        padding: 16px;
        position: relative;
        height: 200px;
    }

    /* Logs card */
    .logs-card {
        background: #161616;
        border: 1px solid #222;
        border-radius: 12px;
        overflow: hidden;
    }
    .logs-card-header {
        padding: 12px 16px;
        border-bottom: 1px solid #1e1e1e;
    }
    .logs-card-title {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #555;
    }
    .log-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 16px;
        border-bottom: 1px solid #1e1e1e;
        font-size: 13px;
        transition: background 0.15s;
    }
    .log-row:last-child { border-bottom: none; }
    .log-row:hover { background: #1a1a1a; }
    .log-time  { color: #444; font-size: 11px; min-width: 72px; flex-shrink: 0; }
    .log-set   { color: #555; font-size: 11px; min-width: 42px; flex-shrink: 0; }
    .log-kg    { color: #f0f0f0; font-weight: 700; min-width: 60px; }
    .log-reps  { color: #555; font-size: 11px; }

    .empty-text {
        padding: 24px 16px;
        font-size: 13px;
        color: #444;
        font-weight: 500;
    }
</style>

<div class="rw">

    <a class="back-link" href="{{ route('client.routines.active') }}">← Volver</a>

    <p class="pg-label">Progreso</p>
    <h1 class="pg-title">{{ $exercise->title }}</h1>

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

    {{-- Historial de sets --}}
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
    const rawLogs = {!! json_encode($chartData) !!};

    const labels  = rawLogs.map(l => l.date);
    const weights = rawLogs.map(l => l.weight);
    const reps    = rawLogs.map(l => l.reps);

    const ctx = document.getElementById('progressChart').getContext('2d');

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
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
                ticks: {
                    color: '#444',
                    font: { family: 'Montserrat', size: 9 },
                    maxTicksLimit: 8,
                    maxRotation: 0,
                },
                grid: { color: '#1e1e1e' },
                border: { color: '#222' },
            },
            y: {
                ticks: {
                    color: '#444',
                    font: { family: 'Montserrat', size: 10 },
                },
                grid: { color: '#1e1e1e' },
                border: { color: '#222' },
            },
        },
    };

    function makeDataset(data, label, unit) {
        return {
            label,
            data,
            borderColor: '#e8ff47',
            backgroundColor: 'rgba(232,255,71,0.06)',
            pointBackgroundColor: '#e8ff47',
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2,
            fill: true,
            tension: 0.35,
        };
    }

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [makeDataset(weights, 'Peso', 'kg')],
        },
        options: commonOptions,
    });

    function switchChart(type, btn) {
        document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        if (type === 'weight') {
            chart.data.datasets[0] = makeDataset(weights, 'Peso', 'kg');
        } else {
            chart.data.datasets[0] = makeDataset(reps, 'Reps', '');
        }
        chart.update();
    }
</script>
@endif

</x-layouts.client>
