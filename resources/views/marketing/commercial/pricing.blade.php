<x-layouts.marketing :meta="$meta" :noindex="true">
    @php
        $scalarInput = static function (string $key, mixed $fallback) {
            $value = old($key, $fallback);
            return is_scalar($value) || $value === null ? $value : $fallback;
        };
        $selectedProducts = old('promotion.plan_codes', $promotion['plan_codes']);
        $selectedProducts = is_array($selectedProducts) ? array_filter($selectedProducts, 'is_string') : [];
    @endphp
    <section class="m-page-head m-wrap">
        <span class="m-eyebrow">ADMINISTRACIÓN DE LA WEB</span>
        <h1>Precios y promociones.</h1>
        <p>Actualizá lo que publicás en la web y activá tu descuento cuando quieras.</p>
        @include('marketing.commercial.navigation')
    </section>
    <section class="m-section m-wrap">
        @if(session('status'))<div class="m-success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())
            <div class="m-error" role="alert">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                @error('catalog_version')<a href="{{ url('/gestion-comercial/precios') }}">Recargar catálogo actualizado</a>@enderror
            </div>
        @endif
        <section class="m-promotion-control" aria-labelledby="promotion-status">
            <div>
                <span class="m-badge">{{ $promotion['enabled'] ? 'Visible en la web' : 'Promoción desactivada' }}</span>
                <h2 id="promotion-status">{{ $promotion['percent'] }} % de descuento</h2>
                <p>{{ $promotion['title'] }}</p>
                <p class="m-small">El botón usa los detalles guardados. Para cambiar el porcentaje o los planes, editá y guardá el formulario de abajo.</p>
            </div>
            <form method="POST" action="{{ url('/gestion-comercial/precios/promocion') }}">
                @csrf
                <input type="hidden" name="catalog_version" value="{{ $version }}">
                <input type="hidden" name="enabled" value="{{ $promotion['enabled'] ? '0' : '1' }}">
                <button class="m-button {{ $promotion['enabled'] ? 'm-button-dark' : '' }}" type="submit">{{ $promotion['enabled'] ? 'Desactivar descuento' : 'Activar descuento' }}</button>
            </form>
        </section>
        <p class="m-note">Estos cambios se aplican a nuevas contrataciones y nuevos acuerdos de plan. Las membresías existentes conservan su importe. Para actualizar una, acordá el valor con su titular y elegí el plan para la próxima renovación desde <a href="{{ url('/gestion-comercial/membresias') }}">Membresías y cobros</a>.</p>

        <form class="m-form m-form-spaced" method="POST" action="{{ url('/gestion-comercial/precios') }}">
            @csrf
            <input type="hidden" name="catalog_version" value="{{ $scalarInput('catalog_version', $version) }}">
            <fieldset class="m-catalog-fieldset">
                <legend>Precios base en pesos argentinos</legend>
                <p class="m-small">Importes mensuales, salvo la rutina puntual que se abona una vez. Podés usar hasta dos decimales.</p>
                <div class="m-catalog-grid">
                    @foreach($items as $code => $item)
                        <label>{{ $item['name'] }}
                            <input type="number" name="prices[{{ $code }}]" min="0.01" max="1000000" step="0.01" inputmode="decimal" required value="{{ $scalarInput('prices.'.$code, number_format($item['base_price_cents'] / 100, 2, '.', '')) }}">
                            <span class="m-small">Publicado ahora: ${{ \App\Services\CommercialAmounts::format($item['price_cents']) }}{{ $item['kind'] === 'one_off' ? ' · pago único' : '/mes' }}{{ $item['discount_percent'] ? ' · '.$item['discount_percent'].' % de descuento' : '' }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <fieldset class="m-catalog-fieldset">
                <legend>Detalles de la promoción</legend>
                <label>Título del cartel<input name="promotion[title]" maxlength="120" required value="{{ $scalarInput('promotion.title', $promotion['title']) }}"></label>
                <label>Texto de la oferta<textarea name="promotion[description]" rows="3" maxlength="280">{{ $scalarInput('promotion.description', $promotion['description']) }}</textarea></label>
                <label>Porcentaje de descuento<input type="number" name="promotion[percent]" min="1" max="99" step="1" required value="{{ $scalarInput('promotion.percent', $promotion['percent']) }}"></label>
                <p class="m-small">Se descuenta del precio base de los productos seleccionados. La oferta permanece activa hasta que la desactives.</p>
                <fieldset class="m-promotion-scope">
                    <legend>Aplicar el descuento a</legend>
                    @foreach($items as $code => $item)
                        <label class="m-checkbox"><input type="checkbox" name="promotion[plan_codes][]" value="{{ $code }}" @checked(in_array($code, $selectedProducts, true))><span>{{ $item['name'] }}</span></label>
                    @endforeach
                </fieldset>
            </fieldset>
            <p class="m-small">Guardar actualiza los precios y el cartel; conserva el estado activo o desactivado de la promoción.</p>
            <button class="m-button" type="submit">Guardar precios y detalles</button>
        </form>
        <div class="m-actions"><a class="m-button m-button-dark" href="{{ url('/planes') }}" target="_blank" rel="noopener">Ver precios publicados ↗</a><a href="{{ url('/') }}" target="_blank" rel="noopener">Ver la portada ↗</a></div>
    </section>
</x-layouts.marketing>
