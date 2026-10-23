<form class="m-form" action="{{ url($type === 'contact' ? '/contacto' : '/'.$type) }}" method="POST">
    @csrf
    @if($errors->any())<div class="m-error" role="alert"><strong>Revisá los datos del formulario.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <label>Nombre<input name="name" required maxlength="120" autocomplete="name" value="{{ old('name') }}"></label>
    <label>Correo electrónico<input type="email" name="email" required maxlength="190" autocomplete="email" value="{{ old('email') }}"></label>
    @if($type === 'contact')<label>¿Qué estás buscando?<select name="audience"><option value="personal" @selected(old('audience', request('audience')) === 'personal')>Una asesoría personal</option><option value="coach" @selected(old('audience', request('audience')) === 'coach')>El sistema para entrenadores</option><option value="gym" @selected(old('audience', request('audience')) === 'gym')>El sistema para un gimnasio</option><option value="other" @selected(old('audience') === 'other')>Otra consulta</option></select></label>@endif
    <label>{{ $type === 'contact' ? 'Plan que te interesa (opcional)' : 'Referencia del servicio o compra (opcional)' }}<input name="plan" maxlength="120" value="{{ old('plan', request('plan')) }}"></label>
    <label>{{ $type === 'contact' ? 'Contanos un poco más' : 'Información adicional (opcional)' }}<textarea name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea></label>
    <div class="m-honeypot" aria-hidden="true"><label>Dejá este campo vacío<input name="website" tabindex="-1" autocomplete="off"></label></div>
    @if($type === 'contact')<label class="m-checkbox"><input type="checkbox" name="privacy_accepted" value="1" required @checked(old('privacy_accepted'))><span>Leí la <a href="{{ route('legal.privacy') }}">política de privacidad</a> y autorizo el uso de estos datos para gestionar esta solicitud.</span></label>@else<p class="m-small">Usamos estos datos únicamente para registrar y gestionar tu solicitud, según la <a href="{{ route('legal.privacy') }}">política de privacidad</a>.</p>@endif
    <button class="m-button" type="submit">{{ $type === 'contact' ? 'Enviar consulta' : 'Registrar solicitud' }} <span aria-hidden="true">↗</span></button>
    <p class="m-small">No te suscribe a publicidad. Recibirás una referencia al enviarlo.</p>
</form>
