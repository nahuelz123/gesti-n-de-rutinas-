@if($item['discount_percent'])
    <p class="m-price-base">Precio base: <del>${{ \App\Services\CommercialAmounts::format($item['base_price_cents']) }}</del></p>
    <span class="m-discount-badge">{{ $item['discount_percent'] }} % de descuento</span>
@endif
<p class="m-price">${{ \App\Services\CommercialAmounts::format($item['price_cents']) }}<small>{{ $frequency ?? '/mes' }}</small></p>
