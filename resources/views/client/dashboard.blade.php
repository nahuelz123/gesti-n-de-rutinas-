<x-layouts.client>

<div class="rw">

    <p class="pg-label">Bienvenido</p>
    <h1 class="pg-title">Mi panel</h1>

    {{-- Rutina activa --}}
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Rutina activa</span>
            </div>
            @if ($active)
                <div class="active-row">
                    <div style="flex:1; min-width:0;">
                        <div class="active-name">{{ $active->routine->title }}</div>
                        <div class="active-date">Asignada el {{ $active->assigned_at?->format('d/m/Y') }}</div>
                    </div>
                    <span class="active-badge">Activa</span>
                    <a class="card-link" href="{{ route('client.routines.active') }}">Ver →</a>
                </div>
            @else
                <div class="empty-text">No tenés una rutina activa.</div>
            @endif
        </div>
    </div>

    {{-- Historial --}}
    <div class="section">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Historial</span>
                <a class="card-link" href="{{ route('client.routines.history') }}">Ver todo →</a>
            </div>
            @forelse ($history as $a)
                <div class="history-row">
                    <div style="flex:1; min-width:0;">
                        <div class="history-name">{{ $a->routine->title }}</div>
                        <div class="history-meta">
                            <span class="history-date">{{ $a->assigned_at?->format('d/m/Y') }}</span>
                            <span class="status-badge {{ $a->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                {{ $a->status }}
                            </span>
                        </div>
                    </div>
                    <a class="card-link" href="{{ route('client.routines.show', $a) }}">Ver →</a>
                </div>
            @empty
                <div class="empty-text">Todavía no hay historial.</div>
            @endforelse
        </div>
    </div>

</div>

</x-layouts.client>