<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    <p class="pg-label">Avisos</p>
    <h1 class="pg-title">Notificaciones</h1>

    <div class="section">
        <div class="section-card">
            @forelse ($notifications as $n)
                <div style="padding:16px 20px; border-bottom:1px solid #1a1a1a;">
                    <div style="font-size:11px; color:#666; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        {{ $n->data['coach_name'] ?? 'VisionFit' }} · {{ $n->created_at->diffForHumans() }}
                    </div>
                    <div style="font-size:14px;">{{ $n->data['message'] ?? '' }}</div>
                </div>
            @empty
                <div class="empty-text">No tenés notificaciones todavía.</div>
            @endforelse
        </div>
    </div>

    <div style="margin-top:20px;">
        {{ $notifications->links('vendor.pagination.visionfit') }}
    </div>
</div>

</x-layouts.client>
