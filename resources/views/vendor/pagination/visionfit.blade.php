@if ($paginator->hasPages())
    <nav class="vf-pagination" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="vf-page-btn vf-page-disabled" aria-hidden="true">←</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="vf-page-btn" rel="prev" aria-label="Anterior">←</a>
        @endif

        <span class="vf-page-info">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="vf-page-btn" rel="next" aria-label="Siguiente">→</a>
        @else
            <span class="vf-page-btn vf-page-disabled" aria-hidden="true">→</span>
        @endif
    </nav>
@endif
