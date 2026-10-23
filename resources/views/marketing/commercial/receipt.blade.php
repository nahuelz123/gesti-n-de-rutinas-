<x-layouts.marketing :meta="$meta" :noindex="true">
    <section class="m-page-head m-wrap m-receipt">
        <span class="m-eyebrow">REGISTRO INTERNO</span><h1>Comprobante de registro.</h1>
        <p>Registro interno de un cobro. No es una factura fiscal ni confirma una transferencia por sí mismo.</p>
        <div class="m-actions m-print-actions"><a href="{{ url(auth()->user()->role === 'super_admin' ? '/gestion-comercial/membresias/'.$subscription->id : '/mi-suscripcion') }}">← Volver</a><button class="m-button m-button-dark" type="button" onclick="window.print()">Imprimir / Guardar PDF</button></div>
    </section>
    <section class="m-section m-wrap m-reading m-receipt">
        <div class="m-form">
            <h2>{{ config('legal.operator_name') ?: 'VisionFit' }}</h2>
            @if(config('legal.tax_id'))<p>Identificación fiscal informada: {{ config('legal.tax_id') }}</p>@endif
            <p>{{ config('legal.contact_email') }}</p>
            <dl class="m-receipt-data">
                <dt>Titular de la membresía</dt><dd>{{ $subscription->user?->name }} · {{ $subscription->user?->email }}</dd>
                <dt>Registro</dt><dd class="m-code">{{ $payment->public_id }}</dd>
                <dt>Fecha del ingreso registrado</dt><dd>{{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y') }}</dd>
                <dt>Importe original</dt><dd>${{ number_format($payment->amount_cents / 100, 2, ',', '.') }} {{ $payment->currency }}</dd>
                <dt>Origen</dt><dd>{{ $payment->source === 'manual' ? 'Registrado manualmente por el equipo' : 'Confirmado mediante el proveedor' }}</dd>
                <dt>Medio informado</dt><dd>{{ ['transfer' => 'Transferencia', 'cash' => 'Efectivo', 'other' => 'Otro medio acordado'][$payment->method] ?? 'Mercado Pago' }}</dd>
                <dt>Estado</dt><dd>{{ ['approved' => 'Aprobado', 'rejected' => 'Rechazado', 'refunded' => 'Reintegrado', 'charged_back' => 'Contracargo'][$payment->status] ?? $payment->status }}</dd>
                <dt>Período asociado</dt><dd>{{ $payment->period_start?->format('d/m/Y H:i') ?? '—' }} al {{ $payment->period_end?->format('d/m/Y H:i') ?? '—' }}</dd>
                @if($payment->reference)<dt>Referencia de la operación</dt><dd class="m-code">{{ $payment->reference }}</dd>@endif
                <dt>Referencia fiscal informada</dt><dd>{{ $payment->fiscal_reference ?: 'No se anotó una referencia fiscal. Solicitá la factura al titular del servicio.' }}</dd>
            </dl>
            @if($payment->refunds->isNotEmpty())<h3>Reintegros registrados</h3>@foreach($payment->refunds as $refund)<p>{{ $refund->performed_on->format('d/m/Y') }} · ${{ number_format($refund->amount_cents / 100, 2, ',', '.') }} ARS · {{ $refund->reason }}</p>@endforeach<p>Saldo del cobro después de reintegros: ${{ number_format(($payment->amount_cents - $payment->refunded_cents) / 100, 2, ',', '.') }} ARS.</p>@endif
        </div>
    </section>
</x-layouts.marketing>
