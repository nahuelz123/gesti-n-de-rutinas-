@php
    $promotion = \App\Support\CommercialCatalog::promotion();
    $products = app(\App\Services\CommercialPricing::class)->products();
@endphp
@if($promotion['enabled'])
    <aside class="m-promotion-banner" data-promotion-banner aria-label="Promoción vigente">
        <div class="m-wrap m-promotion-inner">
            <div>
                <span class="m-promotion-percent">{{ $promotion['percent'] }} % de descuento</span>
                <strong>{{ $promotion['title'] }}</strong>
                @if($promotion['description'])<p>{{ $promotion['description'] }}</p>@endif
                <p class="m-small">Nuevas contrataciones. {{ count($promotion['plan_codes']) === count($products) ? 'Todos los planes y la rutina puntual.' : 'Incluye: '.implode(', ', array_map(fn ($code) => $products[$code]['name'], $promotion['plan_codes'])).'.' }}</p>
            </div>
            <a class="m-button m-button-white" href="{{ url('/planes') }}">Ver la oferta <span aria-hidden="true">↗</span></a>
        </div>
    </aside>
@endif
