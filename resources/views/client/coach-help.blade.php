<x-layouts.client>
    <div class="rw" style="max-width:680px;">
        <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>
        <p class="pg-label">Asistencia durante el entrenamiento</p>
        <h1 class="pg-title">Pedir ayuda al coach</h1>
        <p style="color:var(--clr-text-muted); margin-bottom:20px;">Tu solicitud llegará al panel de tu entrenador dentro de VisionFit. No necesitás compartir tu teléfono.</p>

        @if (session('success'))
            <div role="status" style="padding:14px 16px; border-radius:12px; margin-bottom:16px; background:rgba(22,163,74,.12); border:1px solid rgba(22,163,74,.35);">
                {{ session('success') }}
            </div>
        @endif

        @error('coach')
            <div role="alert" style="padding:14px 16px; border-radius:12px; margin-bottom:16px; background:rgba(220,38,38,.1);">{{ $message }}</div>
        @enderror

        @if (!$coach)
            <div class="empty">Todavía no tenés un coach asignado. Cuando tengas uno, vas a poder pedirle ayuda desde acá.</div>
        @else
            <form method="POST" action="{{ route('client.coach-help.store') }}" style="display:grid; gap:16px;">
                @csrf
                <label style="display:grid; gap:8px;">
                    <span style="font-weight:600;">¿Con qué necesitás ayuda?</span>
                    <select name="reason" required class="chat-input" style="width:100%;">
                        <option value="">Elegí un motivo</option>
                        <option value="tecnica" @selected(old('reason') === 'tecnica')>Técnica de un ejercicio</option>
                        <option value="reemplazo" @selected(old('reason') === 'reemplazo')>Reemplazar un ejercicio</option>
                        <option value="consulta" @selected(old('reason') === 'consulta')>Consulta general</option>
                        <option value="dolor" @selected(old('reason') === 'dolor')>Molestia o dolor</option>
                        <option value="otro" @selected(old('reason') === 'otro')>Otro motivo</option>
                    </select>
                    @error('reason') <span role="alert">{{ $message }}</span> @enderror
                </label>

                <label style="display:grid; gap:8px;">
                    <span style="font-weight:600;">Mensaje adicional (opcional)</span>
                    <textarea name="message" rows="4" maxlength="1000" class="chat-input" style="width:100%; resize:vertical;" placeholder="Contale brevemente qué te pasa...">{{ old('message') }}</textarea>
                    @error('message') <span role="alert">{{ $message }}</span> @enderror
                </label>

                <button type="submit" class="chat-send-btn" style="width:100%; padding:14px;">Enviar solicitud al coach</button>
            </form>
        @endif
    </div>
</x-layouts.client>
