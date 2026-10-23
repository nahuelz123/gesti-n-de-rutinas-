<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Vencimientos de VisionFit</title></head>
<body style="font-family:Arial,sans-serif;color:#252525;line-height:1.6">
    <h1>Membresías por revisar.</h1>
    <p>Resumen para la administración al {{ $generatedAt->format('d/m/Y H:i') }}, hora de Argentina. Hay {{ $total }} membresías para revisar; se muestran hasta 100.</p>
    <table cellpadding="10" cellspacing="0" style="border-collapse:collapse;width:100%">
        <thead><tr><th align="left">Titular / espacio</th><th align="left">Plan</th><th align="left">Vence</th></tr></thead>
        <tbody>
            @foreach($reminders as $subscription)
                <tr>
                    <td>{{ $subscription->user->name }}<br>{{ $subscription->gym?->name ?? 'Asesoría personal' }}</td>
                    <td>{{ config('commerce.plans.'.$subscription->currentPlanCode().'.name', $subscription->currentPlanCode()) }}
                        @if(!$subscription->paid_until)
                            <br>Prueba gratuita
                        @endif
                    </td>
                    <td>{{ $subscription->reminder_due_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p><a href="{{ rtrim(config('commerce.public_url'), '/').'/gestion-comercial/recordatorios' }}">Ver la lista actual y gestionar las membresías</a></p>
    <p>Una renovación posterior a este resumen puede cambiar la lista. Verificá el ingreso real antes de registrar el cobro. Este aviso no cobra dinero ni modifica el acceso.</p>
    <p>Podés desactivar este resumen desde Avisos de vencimiento dentro de la app.</p>
</body>
</html>
