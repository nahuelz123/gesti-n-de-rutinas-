<x-layouts.marketing :meta="$meta" :noindex="true">
    <section class="m-page-head m-wrap">
        <span class="m-eyebrow">TU CUENTA VISIONFIT</span><h1>Mi suscripción.</h1><p>Tu plan, vigencia y comprobantes.</p>
        <div class="m-actions"><a href="{{ url(auth()->user()->role === 'client' ? '/app' : '/admin') }}">← Volver a la app</a><a href="{{ url('/contacto') }}">Pedir ayuda</a></div>
        @include('marketing.commercial.messages')
    </section>
    <section class="m-section m-wrap">
        @if($subscription)
        @php($plan = config('commerce.plans.'.$subscription->currentPlanCode()))
        <div class="m-dashboard">
            <section><span class="m-eyebrow">PLAN ACTUAL</span><h2>{{ $plan['name'] ?? $subscription->currentPlanCode() }}</h2>
                <p class="m-price">${{ \App\Services\CommercialAmounts::format($subscription->currentAmountCents()) }}<small>ARS / mes</small></p>
                @include('marketing.commercial.status')
                @if($subscription->trial_ends_at && !$subscription->paid_until)<p>Prueba hasta el {{ $subscription->trial_ends_at->format('d/m/Y') }}. Sin débito automático.</p>@endif
                @if($subscription->paid_until)<p>Períodos abonados hasta el {{ $subscription->paid_until->format('d/m/Y H:i') }}.</p>@endif
                @if($subscription->next_plan_code && (!$subscription->next_plan_effective_at || $subscription->next_plan_effective_at->isFuture()))<p class="m-note">Próximo plan acordado: {{ config('commerce.plans.'.$subscription->next_plan_code.'.name', $subscription->next_plan_code) }}, ${{ \App\Services\CommercialAmounts::format($subscription->next_amount_cents) }}/mes. {{ $subscription->next_plan_effective_at ? 'Comienza el '.$subscription->next_plan_effective_at->format('d/m/Y').'.' : 'Se aplica en la próxima renovación abonada.' }}</p>@endif
                <p class="m-small m-code">Referencia de tu membresía: {{ $subscription->public_id }}</p>
                <a href="{{ url('/contacto?plan=cambio-'.$subscription->currentPlanCode()) }}">Solicitar cambio de plan</a>
            </section>
            @if($subscription->kind === 'software')
            <section><span class="m-eyebrow">TU ESPACIO</span><h2>{{ $subscription->gym?->name }}</h2><p>{{ $students }} / {{ $plan['students'] ?? '—' }} alumnos habilitados.</p>
                <div class="m-progress" role="progressbar" aria-label="Cupo de alumnos" aria-valuemin="0" aria-valuemax="{{ $plan['students'] ?? 1 }}" aria-valuenow="{{ $students }}"><span style="width:{{ min(100, round($students / max(1, $plan['students'] ?? 1) * 100)) }}%"></span></div>
                <p>Profesionales incluidos: {{ $plan['staff'] ?? '—' }}. El propietario ocupa una plaza.</p><p class="m-small">Archivar un acceso conserva su historial y libera su plaza. Un cambio a un plan menor puede reservar el próximo cupo.</p><a class="m-button" href="{{ url('/admin') }}">Ir a mi espacio ↗</a>
            </section>
            @else
            <section><span class="m-eyebrow">TU ACOMPAÑAMIENTO</span><h2>Entrenamiento personal.</h2><p>La planificación y el seguimiento se coordinan con Nahuel según el servicio acordado. El acceso a la app está incluido.</p><a class="m-button" href="{{ url('/app') }}">Ir a mi entrenamiento ↗</a></section>
            @endif
        </div>
        <div class="m-dashboard m-form-spaced">
            <section><span class="m-eyebrow">PAGO Y CONTINUIDAD</span><h2>{{ $subscription->isManual() ? 'Cómo renovar tu plan.' : 'Tu contratación.' }}</h2>
                @if($subscription->isManual())
                    <p>Tu plan se paga por el medio acordado. No hay un débito automático desde VisionFit.</p>
                    @if(config('commerce.manual_payment_instructions'))<div class="m-note m-payment-instructions">{{ config('commerce.manual_payment_instructions') }}</div>@else<p>Coordiná el pago con el equipo desde <a href="{{ url('/contacto?plan='.$subscription->currentPlanCode()) }}">Contacto</a>.</p>@endif
                    <p>Informá la referencia de la operación y de tu membresía por el canal acordado. El acceso se actualiza cuando el equipo verifica y registra el ingreso.</p>
                    @if(config('legal.contact_email'))<p><a href="mailto:{{ config('legal.contact_email') }}">{{ config('legal.contact_email') }}</a></p>@endif
                @endif
                @if($subscription->cancel_at_period_end)
                    <p>La continuidad está cancelada. Conservás los períodos pagados. Para retomar, <a href="{{ url('/contacto') }}">contactanos</a>.</p>
                @elseif($ready && (!$subscription->isManual() || (!$subscription->paid_until?->isFuture() && !$subscription->next_plan_code)))
                    <form class="m-form m-form-spaced" method="POST" action="{{ url('/mi-suscripcion/checkout') }}">@csrf
                        <label class="m-checkbox"><input type="checkbox" name="renewal_accepted" value="1" required><span>Quiero contratar {{ $plan['name'] ?? $subscription->currentPlanCode() }} por ${{ \App\Services\CommercialAmounts::format($subscription->currentAmountCents()) }} ARS mensuales con renovación automática hasta cancelarla. Leí las <a href="{{ route('legal.terms') }}">condiciones</a>.</span></label>
                        <button class="m-button" type="submit">{{ $subscription->provider_id ? 'Abrir mi contratación en Mercado Pago' : 'Contratar con Mercado Pago' }} ↗</button><p class="m-small">El pago se completa en Mercado Pago. Volver del checkout no confirma un pago.</p>
                    </form>
                @elseif(!$subscription->isManual())<p>El servicio de cobro online requiere atención del equipo. Podés registrar una solicitud de baja si necesitás detener tu renovación.</p>
                @endif
                @if(!$subscription->cancel_at_period_end)<form class="m-form-spaced" method="POST" action="{{ url('/mi-suscripcion/cancelar') }}">@csrf<input type="hidden" name="operation_id" value="{{ Str::uuid() }}"><button class="m-button m-button-dark" type="submit">{{ $subscription->provider_id ? 'Detener renovación' : 'Cancelar continuidad de mi membresía' }}</button></form>@endif
                <p><a href="{{ url('/baja') }}">Registrar una solicitud de baja</a></p>
            </section>
            <section><span class="m-eyebrow">PAGOS</span><h2>Tu historial.</h2>
                <div class="m-table-wrap"><table class="m-table"><thead><tr><th>Fecha</th><th>Importe</th><th>Estado</th><th>Registro</th></tr></thead><tbody>
                    @forelse($payments as $payment)<tr><td>{{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y') }}</td><td>${{ \App\Services\CommercialAmounts::format($payment->amount_cents) }}<br><small>ARS</small></td><td>{{ ['approved' => 'Aprobado', 'rejected' => 'Rechazado', 'refunded' => 'Reintegrado', 'charged_back' => 'Contracargo'][$payment->status] ?? 'En proceso' }}@if($payment->refunded_cents)<br><small>Reintegrado: ${{ \App\Services\CommercialAmounts::format($payment->refunded_cents) }}</small>@endif</td><td><a href="{{ url('/mi-suscripcion/comprobantes/'.$payment->public_id) }}">Comprobante</a></td></tr>@empty<tr><td colspan="4">Todavía no hay pagos registrados.</td></tr>@endforelse
                </tbody></table></div><div class="m-pagination">{{ $payments->links() }}</div>
                <p class="m-small">El registro interno no reemplaza la factura fiscal. Pedila al titular del servicio.</p>
                @if($subscription->provider_id && $ready)<form method="POST" action="{{ url('/mi-suscripcion/actualizar') }}">@csrf<button type="submit" class="m-button m-button-dark">Actualizar estado de cobros</button></form>@endif
            </section>
        </div>
        @else
        <div class="m-note"><strong>Tu cuenta no tiene una suscripción individual administrada aquí.</strong><p>Si sos alumno de un gimnasio o entrenador, tu acceso puede estar incluido en su licencia. Las cuentas anteriores conservan el esquema acordado con el equipo.</p><a href="{{ url('/contacto') }}">Consultar por mi cuenta →</a></div>
        @endif
    </section>
</x-layouts.marketing>
