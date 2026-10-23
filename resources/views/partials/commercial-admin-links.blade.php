<div style="padding:12px 20px;font-size:13px;display:grid;gap:12px">
    <a href="{{ url('/mi-suscripcion') }}">Mi suscripción</a>
    @if(auth()->user()?->role === 'super_admin')
        <a href="{{ url('/gestion-comercial/membresias') }}">Membresías y cobros</a>
        <a href="{{ url('/gestion-comercial') }}">Consultas comerciales</a>
        <a href="{{ url('/gestion-comercial/recordatorios') }}">Avisos de vencimiento</a>
    <a href="{{ url('/gestion-comercial/configuracion') }}">Datos del servicio</a>
    @endif
</div>
