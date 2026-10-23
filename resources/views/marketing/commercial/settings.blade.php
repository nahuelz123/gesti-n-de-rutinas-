<x-layouts.marketing :meta="$meta" :noindex="true">
    <section class="m-page-head m-wrap">
        <span class="m-eyebrow">ADMINISTRACIÓN</span><h1>Datos del servicio.</h1>
        <p>Completá información real antes de ofrecer el servicio. Las instrucciones de pago se muestran solo al titular de la membresía.</p>
        <div class="m-actions"><a href="{{ url('/gestion-comercial') }}">← Gestión comercial</a><a href="{{ url('/gestion-comercial/membresias') }}">Membresías</a></div>
    </section>
    <section class="m-section m-wrap m-reading">
        @if(session('status'))<div class="m-success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="m-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form class="m-form" method="POST" action="{{ url('/gestion-comercial/configuracion') }}">
            @csrf
            <label>Nombre o razón social del titular<input name="operator_name" maxlength="150" value="{{ old('operator_name', $values['operator_name']) }}"></label>
            <label>Correo de contacto y privacidad<input type="email" name="contact_email" maxlength="190" value="{{ old('contact_email', $values['contact_email']) }}"></label>
            <label>Domicilio del titular<input name="operator_address" maxlength="240" value="{{ old('operator_address', $values['operator_address']) }}"></label>
            <label>Identificación fiscal, si corresponde<input name="tax_id" maxlength="35" value="{{ old('tax_id', $values['tax_id']) }}"></label>
            <label>Instrucciones para transferir<textarea name="manual_payment_instructions" rows="6" maxlength="2000" placeholder="Alias o CVU, titular de la cuenta y cómo informar el pago.">{{ old('manual_payment_instructions', $values['manual_payment_instructions']) }}</textarea></label>
            <p class="m-small">No incluyas contraseñas, claves fiscales ni credenciales de pago.</p>
            <label>Espacio de tus asesorías personales<select name="coaching_gym_id"><option value="">Elegir más adelante</option>@foreach($gyms as $gym)<option value="{{ $gym->id }}" @selected((string) old('coaching_gym_id', $values['coaching_gym_id']) === (string) $gym->id)>{{ $gym->name }}</option>@endforeach</select></label>
            <button class="m-button" type="submit">Guardar datos del servicio</button>
        </form>
    </section>
</x-layouts.marketing>
