<x-layouts.marketing :meta="$meta" :noindex="true">
    <section class="m-page-head m-wrap"><span class="m-eyebrow">GESTIÓN MANUAL</span><h1>Avisos de vencimiento.</h1><p>Vos asignás el plan y verificás el cobro. Acá ves a quién contactar para renovar.</p>@include('marketing.commercial.navigation')</section>
    <section class="m-section m-wrap">
        @include('marketing.commercial.messages')
        <p class="m-section-description">Membresías manuales que vencen en los próximos 7 días o vencieron en los últimos 30. Las canceladas y los espacios con acceso anterior sin incorporar no generan avisos. La lista se actualiza al registrar una renovación.</p>
        <div class="m-table-wrap"><table class="m-table"><thead><tr><th>Titular / espacio</th><th>Plan</th><th>Vencimiento</th><th>Gestión</th></tr></thead><tbody>
            @forelse($reminders as $subscription)<tr>
                <td><strong>{{ $subscription->user->name }}</strong><br>{{ $subscription->user->email }}<br>{{ $subscription->gym?->name ?? 'Asesoría personal' }}</td>
                <td>{{ config('commerce.plans.'.$subscription->currentPlanCode().'.name', $subscription->currentPlanCode()) }}<br><small>{{ $subscription->paid_until ? 'Período abonado' : 'Prueba gratuita' }}</small></td>
                <td>{{ $subscription->reminder_due_at->format('d/m/Y H:i') }}<br><strong>{{ $subscription->reminder_due_at->lessThanOrEqualTo(now()) ? 'Vencida' : ($subscription->reminder_due_at->isToday() ? 'Vence hoy' : 'Próxima a vencer') }}</strong></td>
                <td><a class="m-button m-button-small" href="{{ url('/gestion-comercial/membresias/'.$subscription->id) }}">Gestionar</a></td>
            </tr>@empty<tr><td colspan="4">No hay membresías para recordar en este período.</td></tr>@endforelse
        </tbody></table></div><div class="m-pagination">{{ $reminders->links() }}</div>
        <p><a href="{{ url('/gestion-comercial/membresias?state=overdue') }}">Consultar todas las membresías sin período vigente →</a></p>
    </section>
    <section class="m-section m-soft"><div class="m-wrap m-reading"><h2>Recibir un resumen por correo.</h2>
        <p>Un resumen diario desde las 09:00, hora de Argentina. Incluye hasta 100 vencimientos y un enlace al resto. No envía mensajes a tus clientes ni registra cobros.</p>
        <p>{{ $enabled ? 'Activado' : 'Desactivado' }}@if($enabled && $recipient) para {{ $recipient->email }}@elseif($enabled) · La cuenta receptora ya no tiene permiso de administración.@endif.</p>
        @if($enabled && !$mailReady)<p class="m-note">El correo todavía no está listo. Revisá el transporte configurado, la URL HTTPS de la app y que la cuenta receptora siga habilitada como superadministrador.</p>@endif
        @if($latestRun)<div class="m-note"><strong>Último intento: {{ $latestRun->day->format('d/m/Y') }}.</strong>
            @if($latestRun->status === 'sent') El transporte aceptó el resumen. La recepción final depende del servicio de correo.
            @elseif($latestRun->status === 'empty') No había vencimientos para enviar.
            @elseif($latestRun->status === 'revoked') Aviso cancelado antes del envío por un cambio de permisos o de configuración.
            @elseif($latestRun->status === 'failed') El transporte no confirmó el envío. Revisá la configuración y tu bandeja. No se reintenta automáticamente ese día.
            @else Envío sin confirmar. Revisá tu bandeja antes de repetir cualquier gestión; el próximo resumen corresponde al siguiente día.
            @endif</div>@endif
        <form class="m-form" method="POST" action="{{ url('/gestion-comercial/recordatorios/configuracion') }}">@csrf
            <input type="hidden" name="reminders_enabled" value="0">
            <label class="m-checkbox"><input type="checkbox" name="reminders_enabled" value="1" @checked($enabled && $recipient?->id === auth()->id())><span>Enviar el resumen al correo de mi cuenta: {{ auth()->user()->email }}.</span></label>
            <label class="m-checkbox"><input type="checkbox" name="confirmed" value="1" required><span>Confirmo este ajuste. Si lo activo, autorizo el envío de los datos comerciales a mi correo.</span></label>
            <button class="m-button" type="submit">Guardar aviso por correo</button>
        </form>
        <p class="m-small">Requiere un servicio de correo configurado. Si un envío falla, se informa acá y no se repite automáticamente ese día para evitar duplicados. La lista del panel sigue actualizada.</p>
    </div></section>
</x-layouts.marketing>
