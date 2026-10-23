<x-layouts.marketing :meta="$meta" :noindex="true">
    @php
        $hasUpcomingPlan = $subscription->next_plan_code && (!$subscription->next_plan_effective_at || $subscription->next_plan_effective_at->isFuture());
        $plan = config('commerce.plans.'.$subscription->currentPlanCode());
        $renewalAmount = $subscription->next_plan_code ? $subscription->next_amount_cents : $subscription->currentAmountCents();
        $starts = $subscription->paid_until?->isFuture() ? $subscription->paid_until->copy() : now();
    @endphp
    <section class="m-page-head m-wrap"><span class="m-eyebrow">GESTIÓN DE MEMBRESÍA</span><h1>{{ $subscription->user?->name }}</h1><p>{{ $subscription->user?->email }} · {{ $subscription->gym?->name ?? 'Asesoría personal' }}</p>@include('marketing.commercial.navigation')</section>
    <section class="m-section m-wrap">
        @include('marketing.commercial.messages')
        <div class="m-dashboard">
            <section><span class="m-eyebrow">PLAN Y ACCESO</span><h2>{{ $plan['name'] ?? $subscription->currentPlanCode() }}</h2><p>${{ number_format($subscription->currentAmountCents() / 100, 0, ',', '.') }} ARS por mes.</p>@include('marketing.commercial.status')
                <p>Pagado hasta: <strong>{{ $subscription->paid_until?->format('d/m/Y H:i') ?? 'Sin cobros registrados' }}</strong>.</p>
                @if($subscription->trial_ends_at)<p>Prueba hasta {{ $subscription->trial_ends_at->format('d/m/Y') }}.</p>@endif
                @if($usage)<p>Alumnos habilitados: {{ $usage['students'] }} / {{ $plan['students'] ?? '—' }}. Profesionales: {{ $usage['staff'] }} / {{ $plan['staff'] ?? '—' }}.</p>@endif
                @if($subscription->gym && !$subscription->gym->billing_managed)<p class="m-note">Este espacio mantiene su acceso anterior. El primer cobro confirmado lo incorpora al control de vencimientos y cupos.</p>@endif
                <p class="m-small m-code">Referencia: {{ $subscription->public_id }}</p>
            </section>
            <section><span class="m-eyebrow">PRÓXIMO CICLO</span><h2>Lo acordado.</h2>
                @if($hasUpcomingPlan)<p>{{ config('commerce.plans.'.$subscription->next_plan_code.'.name', $subscription->next_plan_code) }} por ${{ number_format($subscription->next_amount_cents / 100, 0, ',', '.') }}/mes.</p><p>{{ $subscription->next_plan_effective_at ? 'Se aplica desde el '.$subscription->next_plan_effective_at->format('d/m/Y H:i').'.' : 'Se aplica al comenzar el próximo período que se registre como pagado.' }}</p>@else<p>No hay cambio de plan programado.</p>@endif
                <p>Un cambio no modifica los meses ya pagados ni genera un cobro proporcional. No se editan los cobros originales.</p>
                @if(!$subscription->isManual())<p class="m-note">Esta cuenta está vinculada a Mercado Pago. Sus cobros y cancelación se confirman con el proveedor; las operaciones manuales están bloqueadas.</p>@endif
            </section>
        </div>
    </section>
    @if($subscription->isManual())
    <section class="m-section m-soft"><div class="m-wrap m-two">
        <section><h2>Registrar cobro recibido.</h2><p class="m-section-description">Solo después de verificar el ingreso. El período comienza el {{ $starts->format('d/m/Y H:i') }}. Un reintegro se registra por separado.</p>
            @if($subscription->cancel_at_period_end)<div class="m-note">La membresía está cancelada. Reanudala antes de registrar otro período.</div>@else
            <form class="m-form" method="POST" action="{{ url('/gestion-comercial/membresias/'.$subscription->id.'/cobros') }}">@csrf
                <input type="hidden" name="operation_id" value="{{ Str::uuid() }}">
                <label>Meses completos<select name="months" required>@for($months = 1; $months <= 12; $months++)<option value="{{ $months }}">{{ $months }} {{ $months === 1 ? 'mes' : 'meses' }} · ${{ number_format($renewalAmount * $months / 100, 0, ',', '.') }} · hasta {{ $starts->copy()->addMonthsNoOverflow($months)->format('d/m/Y') }}</option>@endfor</select></label>
                <label>Importe recibido en ARS<input type="number" inputmode="decimal" min="0.01" step="0.01" name="amount" value="{{ number_format($renewalAmount / 100, 2, '.', '') }}" required></label>
                <label>Medio de pago<select name="method"><option value="transfer">Transferencia</option><option value="cash">Efectivo</option><option value="other">Otro medio acordado</option></select></label>
                <label>Referencia completa de la operación<input name="reference" maxlength="120"><small>Obligatoria y única para transferencias. No ingreses datos de tarjeta.</small></label>
                <label>Fecha del ingreso<input type="date" name="paid_on" required value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></label>
                <label>Referencia fiscal, si ya emitiste la factura<input name="fiscal_reference" maxlength="120"></label>
                <label class="m-checkbox"><input type="checkbox" name="confirmed" value="1" required><span>Verifiqué el ingreso real y el importe corresponde a los meses elegidos. Este registro no ejecuta un cobro.</span></label>
                <button class="m-button" type="submit">Confirmar registro del cobro</button>
            </form>@endif
        </section>
        <section><h2>Plan y continuidad.</h2><p class="m-section-description">Guardá el acuerdo con el titular. Para bajar de plan, primero archivá accesos que excedan el nuevo cupo.</p>
            @if(!$subscription->next_plan_effective_at || $subscription->next_plan_effective_at->lessThanOrEqualTo(now()))
            <form class="m-form" method="POST" action="{{ url('/gestion-comercial/membresias/'.$subscription->id.'/plan') }}">@csrf<input type="hidden" name="operation_id" value="{{ Str::uuid() }}">
                <label>Plan para la próxima renovación<select name="plan_code"><option value="">Retirar un cambio pendiente</option>@foreach(config('commerce.plans') as $code => $choice)@if($choice['kind'] === $subscription->kind)<option value="{{ $code }}">{{ $choice['name'] }} · ${{ number_format($choice['price'], 0, ',', '.') }}/mes</option>@endif@endforeach</select></label>
                <label>Motivo o acuerdo<textarea name="reason" required minlength="5" maxlength="500" rows="2"></textarea></label>
                <label class="m-checkbox"><input type="checkbox" name="confirmed" value="1" required><span>El cambio está acordado con el titular y entra en su próximo período.</span></label>
                <button class="m-button m-button-dark" type="submit">Guardar próximo plan</button>
            </form>@endif
            <form class="m-form m-form-spaced" method="POST" action="{{ url('/gestion-comercial/membresias/'.$subscription->id.($subscription->cancel_at_period_end ? '/reanudar' : '/baja')) }}">@csrf<input type="hidden" name="operation_id" value="{{ Str::uuid() }}">
                <label>Motivo de {{ $subscription->cancel_at_period_end ? 'reanudación' : 'baja' }}<textarea name="reason" required minlength="5" maxlength="500" rows="2"></textarea></label>
                <label class="m-checkbox"><input type="checkbox" name="confirmed" value="1" required><span>Confirmo la operación. Los períodos abonados conservan su fecha y no se mueve dinero.</span></label>
                <button class="m-button m-button-dark" type="submit">{{ $subscription->cancel_at_period_end ? 'Reanudar membresía' : 'Registrar baja' }}</button>
            </form>
        </section>
    </div></section>
    @endif
    <section class="m-section m-wrap"><h2>Cobros y reintegros.</h2><p class="m-section-description">Cada registro conserva su importe y período originales. Un reintegro completo retira ese período; uno parcial conserva su vigencia.</p>
        @forelse($payments as $payment)<article class="m-payment-entry">
            <div class="m-payment-heading"><div><h3>${{ number_format($payment->amount_cents / 100, 0, ',', '.') }} ARS</h3><p>{{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y') }} · {{ $payment->source === 'manual' ? 'Cobro manual' : 'Mercado Pago' }} · {{ $payment->status === 'refunded' ? 'Reintegrado completo' : ($payment->status === 'approved' ? 'Aprobado' : $payment->status) }}</p><p>Período: {{ $payment->period_start?->format('d/m/Y H:i') ?? '—' }} → {{ $payment->period_end?->format('d/m/Y H:i') ?? '—' }}</p><p class="m-code">{{ $payment->reference ?? $payment->provider_payment_id }}</p></div><a class="m-button m-button-small m-button-dark" href="{{ url('/mi-suscripcion/comprobantes/'.$payment->public_id) }}">Comprobante</a></div>
            @if($payment->fiscal_reference)<p>Referencia fiscal informada: {{ $payment->fiscal_reference }}</p>@endif
            @foreach($payment->refunds as $refund)<p>Reintegro del {{ $refund->performed_on->format('d/m/Y') }}: ${{ number_format($refund->amount_cents / 100, 0, ',', '.') }} · {{ $refund->reason }}</p>@endforeach
            @if($subscription->isManual() && $payment->source === 'manual')<div class="m-two">
                @if($payment->status === 'approved' && $payment->amount_cents > $payment->refunded_cents)<details class="m-commercial-details"><summary>Registrar reintegro ya realizado</summary><form class="m-form" method="POST" action="{{ url('/gestion-comercial/pagos/'.$payment->id.'/reintegros') }}">@csrf<input type="hidden" name="operation_id" value="{{ Str::uuid() }}">
                    <p class="m-small">Saldo reintegrable: ${{ number_format(($payment->amount_cents - $payment->refunded_cents) / 100, 2, ',', '.') }}. VisionFit no envía el dinero.</p>
                    <label>Importe reintegrado en ARS<input type="number" name="amount" min="0.01" step="0.01" max="{{ number_format(($payment->amount_cents - $payment->refunded_cents) / 100, 2, '.', '') }}" required></label>
                    <label>Fecha de reintegro<input type="date" name="performed_on" required value="{{ now()->toDateString() }}" min="{{ $payment->paid_at?->toDateString() }}" max="{{ now()->toDateString() }}"></label>
                    <label>Motivo / referencia de devolución<textarea name="reason" rows="2" minlength="5" maxlength="500" required></textarea></label>
                    <label class="m-checkbox"><input type="checkbox" name="confirmed" value="1" required><span>Ya realicé esta devolución por el medio acordado y verifiqué el importe.</span></label><button class="m-button m-button-dark" type="submit">Registrar reintegro</button>
                </form></details>@endif
                <details class="m-commercial-details"><summary>Anotar o corregir referencia fiscal</summary><form class="m-form" method="POST" action="{{ url('/gestion-comercial/pagos/'.$payment->id.'/referencia-fiscal') }}">@csrf<input type="hidden" name="operation_id" value="{{ Str::uuid() }}">
                    <label>Referencia de factura emitida fuera de VisionFit<input name="fiscal_reference" value="{{ $payment->fiscal_reference }}" required maxlength="120"></label><label class="m-checkbox"><input type="checkbox" name="confirmed" value="1" required><span>El comprobante fiscal ya fue emitido. Solo anoto su referencia.</span></label><button class="m-button m-button-dark" type="submit">Anotar referencia</button>
                </form></details>
            </div>@endif
        </article>@empty<div class="m-note">Todavía no hay cobros en esta membresía.</div>@endforelse
        <div class="m-pagination">{{ $payments->withQueryString()->links() }}</div>
    </section>
    <section class="m-section m-soft"><div class="m-wrap"><h2>Historial de operaciones.</h2><div class="m-table-wrap"><table class="m-table"><thead><tr><th>Fecha</th><th>Quién</th><th>Operación</th><th>Detalle registrado</th></tr></thead><tbody>@forelse($operations as $operation)<tr><td>{{ $operation->created_at->format('d/m/Y H:i') }}</td><td>{{ $operation->actor_name }}</td><td>{{ ['subscription_assigned' => 'Alta de membresía', 'payment_recorded' => 'Cobro registrado', 'refund_recorded' => 'Reintegro registrado', 'plan_scheduled' => 'Cambio de plan', 'subscription_cancelled' => 'Baja', 'subscription_resumed' => 'Reanudación', 'fiscal_reference_added' => 'Referencia fiscal'][$operation->action] ?? $operation->action }}</td><td><details><summary>Ver detalle</summary><pre class="m-audit-data">{{ json_encode(['datos' => $operation->payload, 'resultado' => $operation->result], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></td></tr>@empty<tr><td colspan="4">Sin operaciones manuales registradas.</td></tr>@endforelse</tbody></table></div><div class="m-pagination">{{ $operations->withQueryString()->links() }}</div></div></section>
</x-layouts.marketing>
