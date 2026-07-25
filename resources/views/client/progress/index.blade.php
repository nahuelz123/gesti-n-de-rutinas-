<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    @if (session('success'))
        <div class="alert-ok">{{ session('success') }}</div>
    @endif

    <p class="pg-label">Seguimiento</p>
    <h1 class="pg-title">Mi progreso</h1>

    @if (!$hasProfile)
        <div class="section">
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title">Antes de arrancar</span>
                </div>
                <div style="padding:18px 20px;">
                    <p class="empty-text" style="padding:0 0 14px; text-align:left;">
                        Para calcular tu % de grasa corporal necesitamos tu altura y sexo biológico (solo una vez).
                    </p>
                    <form method="POST" action="{{ route('client.progress.profile') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                        @csrf
                        <div>
                            <label style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:#666; display:block; margin-bottom:6px;">Altura (cm)</label>
                            <input type="number" name="height_cm" step="0.1" min="100" max="250" required class="chat-input" style="width:110px;">
                        </div>
                        <div>
                            <label style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:#666; display:block; margin-bottom:6px;">Sexo biológico</label>
                            <select name="sex" required class="chat-input" style="width:140px;">
                                <option value="">Elegir...</option>
                                <option value="m">Masculino</option>
                                <option value="f">Femenino</option>
                            </select>
                        </div>
                        <button type="submit" class="chat-send-btn">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Hero: último peso + tendencia + % grasa, estilo pr-card --}}
    @if ($latest)
        @php
            $weightDiff = ($latest->weight !== null && $previous?->weight !== null)
                ? round($latest->weight - $previous->weight, 1)
                : null;
        @endphp
        <div class="pr-card">
            <div>
                <div class="pr-label">Peso actual</div>
                @if ($latest->weight)
                    <div class="pr-value">{{ $latest->weight }}<span class="pr-unit">kg</span></div>
                    @if ($weightDiff !== null)
                        <div style="font-size:12px; font-weight:700; margin-top:4px; color: {{ $weightDiff <= 0 ? '#4ade80' : '#e63946' }};">
                            {{ $weightDiff > 0 ? '+' : '' }}{{ $weightDiff }}kg desde la última medición
                        </div>
                    @endif
                @else
                    <div class="pr-none">—</div>
                @endif
            </div>
            <div class="last-log">
                <div class="last-log-label">% Grasa corporal</div>
                <div class="last-log-date">{{ $latest->measured_at->format('d/m/Y') }}</div>
                <div class="last-log-detail">
                    {{ $latest->body_fat_percentage ? $latest->body_fat_percentage.'%' : 'Sin datos suficientes' }}
                </div>
            </div>
        </div>
    @endif

    {{-- Gráfico de evolución (Chart.js, con tabs) --}}
    @if ($chartData->count() >= 2)
        <div class="chart-card">
            <div class="chart-card-header">
                <span class="chart-card-title">Evolución</span>
                <div class="chart-tabs">
                    <button class="chart-tab active" onclick="switchProgressChart('weight', this)">Peso</button>
                    <button class="chart-tab" onclick="switchProgressChart('body_fat_percentage', this)">% Grasa</button>
                    <button class="chart-tab" onclick="switchProgressChart('waist', this)">Cintura</button>
                </div>
            </div>
            <div class="chart-wrap">
                <canvas id="progressBodyChart"></canvas>
            </div>
        </div>
    @endif

    {{-- Historial --}}
    <div class="logs-card">
        <div class="logs-card-header">
            <span class="logs-card-title">Historial de mediciones</span>
        </div>
        @forelse ($measurements as $m)
            <div class="log-row" style="justify-content:space-between;">
                <span class="log-time" style="min-width:80px;">{{ $m->measured_at->format('d/m/Y') }}</span>
                <span class="log-kg">{{ $m->weight ? $m->weight.'kg' : '—' }}</span>
                <span class="log-reps">{{ $m->body_fat_percentage ? $m->body_fat_percentage.'% grasa' : '' }}</span>
                <form method="POST" action="{{ route('client.progress.destroy', $m) }}" onsubmit="return confirm('¿Eliminar esta medición?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="link-btn" style="font-size:10px;">Eliminar</button>
                </form>
            </div>
        @empty
            <div class="empty-text">Todavía no cargaste ninguna medición.</div>
        @endforelse
    </div>

    {{-- Formulario nueva medición --}}
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Nueva medición</span>
            </div>
            <form method="POST" action="{{ route('client.progress.store') }}" style="padding:16px 20px; display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                @csrf
                <div style="grid-column: 1 / -1;">
                    <label style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:#666; display:block; margin-bottom:6px;">Fecha</label>
                    <input type="date" name="measured_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required class="chat-input" style="width:100%;">
                </div>

                @foreach ([
                    'weight' => 'Peso (kg)', 'waist' => 'Cintura (cm)', 'chest' => 'Pecho/busto (cm)',
                    'hip' => 'Cadera (cm)', 'arm' => 'Brazo (cm)', 'thigh' => 'Muslo (cm)', 'neck' => 'Cuello (cm)',
                ] as $field => $label)
                    <div>
                        <label style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:#666; display:block; margin-bottom:6px;">{{ $label }}</label>
                        <input type="number" name="{{ $field }}" step="0.1" class="chat-input" style="width:100%;">
                    </div>
                @endforeach

                <div style="grid-column: 1 / -1;">
                    <label style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:#666; display:block; margin-bottom:6px;">Notas (opcional)</label>
                    <input type="text" name="notes" maxlength="500" class="chat-input" style="width:100%;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="chat-send-btn" style="width:100%;">Guardar medición</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($chartData->count() >= 2)
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
@php
    $bodyChartData = $chartData->map(function ($m) {
        return [
            'date' => $m->measured_at->format('d/m'),
            'weight' => $m->weight !== null ? (float) $m->weight : null,
            'body_fat_percentage' => $m->body_fat_percentage !== null ? (float) $m->body_fat_percentage : null,
            'waist' => $m->waist !== null ? (float) $m->waist : null,
        ];
    });
@endphp
const rawMeasurements = {!! json_encode($bodyChartData) !!};
const bLabels = rawMeasurements.map(m => m.date);

const bCtx = document.getElementById('progressBodyChart').getContext('2d');

const bCommonOptions = {
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

function bMakeDataset(data) {
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
        spanGaps: true,
    };
}

const bodyChart = new Chart(bCtx, {
    type: 'line',
    data: { labels: bLabels, datasets: [bMakeDataset(rawMeasurements.map(m => m.weight))] },
    options: bCommonOptions,
});

function switchProgressChart(field, btn) {
    document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    bodyChart.data.datasets[0] = bMakeDataset(rawMeasurements.map(m => m[field]));
    bodyChart.update();
}
</script>
@endif

</x-layouts.client>
