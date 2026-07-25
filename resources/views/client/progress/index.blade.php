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

    {{-- Gráfico de peso --}}
    @if ($chartData->count() >= 2)
        @php
            $weights = $chartData->pluck('weight')->filter()->values();
            $min = $weights->min();
            $max = $weights->max();
            $range = max($max - $min, 1);
            $points = $chartData->filter(fn($m) => $m->weight !== null)->values();
            $n = $points->count();

            $coords = $points->map(function ($m, $i) use ($n, $min, $range) {
                $x = $n > 1 ? ($i / ($n - 1)) * 280 : 140;
                $y = 60 - (($m->weight - $min) / $range) * 50;
                return "{$x},{$y}";
            })->implode(' ');
        @endphp
        <div class="section">
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title">Evolución de peso</span>
                    <span class="card-link" style="color:#666;">{{ $weights->last() }}kg</span>
                </div>
                <div style="padding:16px 20px;">
                    <svg viewBox="0 0 280 60" style="width:100%; height:80px;" preserveAspectRatio="none">
                        <polyline points="{{ $coords }}" fill="none" stroke="#e63946" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
        </div>
    @endif

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

    {{-- Historial --}}
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Historial</span>
            </div>
            @forelse ($measurements as $m)
                <div style="padding:14px 20px; border-bottom:1px solid #1a1a1a; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <div>
                        <div style="font-size:13px; font-weight:700;">{{ $m->measured_at->format('d/m/Y') }}</div>
                        <div style="font-size:11px; color:#666; margin-top:2px;">
                            @if($m->weight) {{ $m->weight }}kg @endif
                            @if($m->body_fat_percentage) · {{ $m->body_fat_percentage }}% grasa corporal @endif
                            @if(!$m->weight && !$m->body_fat_percentage) Sin peso ni % de grasa cargados @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('client.progress.destroy', $m) }}" onsubmit="return confirm('¿Eliminar esta medición?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-btn">Eliminar</button>
                    </form>
                </div>
            @empty
                <div class="empty-text">Todavía no cargaste ninguna medición.</div>
            @endforelse
        </div>
    </div>
</div>

</x-layouts.client>
