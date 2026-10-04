<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    @if (session('success'))
        <div class="alert-ok">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-ok">⚠️ {{ $errors->first() }}</div>
    @endif

    <p class="pg-label">Mi cuenta</p>
    <h1 class="pg-title">Perfil y objetivos</h1>

    <div class="account-card">
        <div class="account-card-title">Tu perfil</div>
        <div class="account-card-hint">
            Esta información le llega a tu coach para que diseñe tu rutina y tu dieta a medida. Podés actualizarla cuando quieras.
        </div>

        <form method="POST" action="{{ route('client.account.update') }}" class="account-form">
            @csrf
            @method('PUT')

            <div class="account-field">
                <label>Nombre</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="account-field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="account-row">
                <div class="account-field">
                    <label>Edad</label>
                    <input type="number" name="age" min="10" max="100" value="{{ old('age', $user->age) }}" placeholder="Ej: 28">
                </div>

                <div class="account-field" style="flex:2;">
                    <label>Nivel de actividad</label>
                    <select name="activity_level">
                        <option value="">Sin especificar</option>
                        @foreach ($activityLevels as $key => $label)
                            <option value="{{ $key }}" @selected(old('activity_level', $user->activity_level) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="account-field">
                <label>Objetivos</label>
                <textarea name="goals" rows="3" placeholder="Ej: bajar grasa y ganar masa muscular, mejorar resistencia, prepararme para una competencia...">{{ old('goals', $user->goals) }}</textarea>
            </div>

            <div class="account-field">
                <label>¿Alguna lesión u observación médica?</label>
                <textarea name="medical_notes" rows="3" placeholder="Ej: dolor lumbar crónico, cirugía de rodilla en 2023, evitar impacto...">{{ old('medical_notes', $user->medical_notes) }}</textarea>
            </div>

            <div class="account-field" style="border-top:1px solid #2a2a2a; padding-top:14px;">
                <input type="hidden" name="ai_data_processing_consent" value="0">
                <label style="display:flex; align-items:flex-start; gap:9px; line-height:1.5;">
                    <input type="checkbox" name="ai_data_processing_consent" value="1" @checked(old('ai_data_processing_consent', $user->hasActiveConsent('ai_data_processing'))) style="margin-top:3px;">
                    <span>Autorizo que mis mensajes y el contexto necesario de mi perfil, rutina, dieta y progreso se envíen al proveedor de IA para responder en el chat. Es opcional y puedo retirar el permiso cuando quiera.</span>
                </label>
                <p style="font-size:12px; color:#777; margin-top:8px;">El profe solo puede usar el asistente con tus datos si activás esta opción. <a href="{{ route('legal.privacy', ['gym' => $user->gym?->invite_code]) }}" style="color:#fbbf24; text-decoration:underline;">Ver privacidad</a>.</p>
            </div>

            <button type="submit" class="lf-btn" style="align-self:flex-start;">Guardar cambios</button>
        </form>
    </div>

    <div class="account-card">
        <div class="account-card-title">Cambiar contraseña</div>

        <form method="POST" action="{{ route('client.account.password.update') }}" class="account-form">
            @csrf
            @method('PUT')

            <div class="account-field">
                <label>Contraseña actual</label>
                <input type="password" name="current_password" required autocomplete="current-password">
            </div>

            <div class="account-field">
                <label>Contraseña nueva</label>
                <input type="password" name="password" required autocomplete="new-password">
            </div>

            <div class="account-field">
                <label>Confirmar contraseña nueva</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password">
            </div>

            <button type="submit" class="lf-btn" style="align-self:flex-start;">Actualizar contraseña</button>
        </form>
    </div>
</div>

</x-layouts.client>
