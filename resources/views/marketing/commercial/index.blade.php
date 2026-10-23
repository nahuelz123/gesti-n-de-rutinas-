<x-layouts.marketing :meta="$meta" :noindex="true">
    <section class="m-page-head m-wrap">
        <span class="m-eyebrow">ADMINISTRACIÓN</span><h1>Membresías y cobros.</h1>
        <p>Cobrá por el canal acordado y registrá cada ingreso después de verificarlo. VisionFit administra los períodos y accesos.</p>
        @include('marketing.commercial.navigation')
    </section>
    <section class="m-section m-wrap">
        @include('marketing.commercial.messages')
        <div class="m-metrics">
            <article><span>Membresías</span><strong>{{ $totalCount }}</strong></article>
            <article><span>Con período vigente</span><strong>{{ $activeCount }}</strong></article>
            <article><span>Recibido este mes</span><strong>${{ \App\Services\CommercialAmounts::format((int) $received) }}</strong></article>
            <article><span>Reintegrado este mes</span><strong>${{ \App\Services\CommercialAmounts::format((int) $returned) }}</strong></article>
        </div>
        <form class="m-filter-form" method="GET" action="{{ url('/gestion-comercial/membresias') }}">
            <label>Buscar titular, correo o espacio<input name="search" value="{{ $search }}" maxlength="120" type="search"></label>
            <label>Estado<select name="state">@foreach(['all' => 'Todas', 'active' => 'Con acceso vigente', 'overdue' => 'Sin período vigente', 'trial' => 'En prueba', 'expiring' => 'Vencen en 7 días', 'cancelled' => 'Canceladas'] as $value => $label)<option value="{{ $value }}" @selected($state === $value)>{{ $label }}</option>@endforeach</select></label>
            <button class="m-button" type="submit">Filtrar</button>
        </form>
        <div class="m-table-wrap"><table class="m-table">
            <thead><tr><th>Titular / espacio</th><th>Plan</th><th>Período</th><th>Estado</th><th>Gestión</th></tr></thead>
            <tbody>@forelse($subscriptions as $subscription)<tr>
                <td><strong>{{ $subscription->user?->name }}</strong><br>{{ $subscription->user?->email }}<br>{{ $subscription->gym?->name }}</td>
                <td>{{ config('commerce.plans.'.$subscription->currentPlanCode().'.name', $subscription->currentPlanCode()) }}<br>${{ \App\Services\CommercialAmounts::format($subscription->currentAmountCents()) }}/mes<br><small>{{ $subscription->isManual() ? 'Cobro manual' : 'Mercado Pago' }}</small></td>
                <td>{{ $subscription->paid_until?->format('d/m/Y') ?? $subscription->trial_ends_at?->format('d/m/Y') ?? 'Pendiente del primer cobro' }}</td>
                <td>@include('marketing.commercial.status') @if($subscription->gym && !$subscription->gym->billing_managed)<p class="m-small">Esquema anterior conservado hasta el primer cobro.</p>@endif</td>
                <td><a class="m-button m-button-small" href="{{ url('/gestion-comercial/membresias/'.$subscription->id) }}">Abrir</a></td>
            </tr>@empty<tr><td colspan="5">No hay membresías que coincidan con esta búsqueda.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="m-pagination">{{ $subscriptions->links() }}</div>
    </section>
    <section class="m-section m-soft"><div class="m-wrap m-two">
        <section><h2>Asignar una membresía.</h2><p class="m-section-description">Creá primero la cuenta en Usuarios. Para software, elegí al administrador del espacio. Para asesorías, al cliente del espacio configurado en Datos del servicio.</p>
            <form class="m-form" method="POST" action="{{ url('/gestion-comercial/membresias') }}">@csrf
                <input type="hidden" name="operation_id" value="{{ Str::uuid() }}">
                <label>Titular<select name="user_id" required><option value="">Elegir cuenta</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected((string) old('user_id') === (string) $account->id)>{{ $account->name }} · {{ $account->email }} · {{ $account->role === 'client' ? 'Asesoría' : 'Licencia' }}</option>@endforeach</select></label>
                <label>Plan<select name="plan_code" required>@foreach(\App\Support\CommercialCatalog::plans() as $code => $plan)<option value="{{ $code }}" @selected(old('plan_code') === $code)>{{ $plan['name'] }} · ${{ \App\Services\CommercialAmounts::format($plan['price_cents']) }}/mes</option>@endforeach</select></label>
                <label class="m-checkbox"><input type="checkbox" name="confirmed" value="1" required><span>Confirmo que esta membresía está acordada con el titular. Prepararla no cobra dinero.</span></label>
                <button class="m-button" type="submit">Preparar membresía manual</button>
            </form>
        </section>
        <section><h2>Exportar movimientos.</h2><p class="m-section-description">Cobros manuales y reintegros en filas separadas. Los importes están en ARS. La exportación no emite facturas.</p>
            <form class="m-form" method="GET" action="{{ url('/gestion-comercial/movimientos.csv') }}">
                <label>Desde<input type="date" name="from" required value="{{ now()->startOfMonth()->toDateString() }}"></label>
                <label>Hasta<input type="date" name="to" required value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></label>
                <button class="m-button m-button-dark" type="submit">Descargar CSV</button>
            </form>
            <div class="m-note"><strong>Primero verificá el ingreso.</strong>No habilites una membresía por una captura o un mensaje. Revisá el dinero en tu cuenta y usá la referencia completa de la operación.</div>
        </section>
    </div></section>
</x-layouts.marketing>
