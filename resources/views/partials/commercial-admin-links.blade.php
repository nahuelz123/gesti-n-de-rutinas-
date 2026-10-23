<div style="padding:12px 20px;font-size:13px;display:grid;gap:12px">
    <a href="{{ url('/mi-suscripcion') }}">Mi suscripción</a>
    @if(auth()->user()?->role === 'super_admin')<a href="{{ url('/gestion-comercial') }}">Consultas comerciales</a>@endif
</div>
