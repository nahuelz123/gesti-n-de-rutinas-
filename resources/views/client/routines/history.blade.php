<x-layouts.client>

<div class="rw">

    <p class="pg-label">Mis rutinas</p>
    <h1 class="pg-title">Historial</h1>

    @if ($assignments->isEmpty())
        <div class="empty">No hay rutinas registradas.</div>
    @else
        <div class="section-card">
            @foreach ($assignments as $a)
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
            @endforeach

            @if ($assignments->hasPages())
                <div class="pagination-wrap">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    @endif

</div>

</x-layouts.client>