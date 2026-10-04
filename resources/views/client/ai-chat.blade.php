<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    <p class="pg-label">Asistente virtual</p>
    <h1 class="pg-title">Preguntale a VisionFit AI</h1>

    @unless ($hasAiConsent)
        <div id="ai-consent-note" role="note" style="margin-bottom:14px; padding:13px 15px; border:1px solid #5b4924; border-radius:12px; background:#241d0e; color:#ead9ac; font-size:13px; line-height:1.5;">
            Para responder, el asistente envía tus mensajes y el contexto necesario de tu perfil, rutina, dieta y progreso a {{ $aiProviderHost }}. Es opcional. Marcá la autorización antes de enviar; podés retirarla desde <a href="{{ route('client.account.edit') }}" style="color:#fbbf24; text-decoration:underline;">Mi cuenta</a>.
            <label style="display:flex; align-items:flex-start; gap:9px; margin-top:10px; color:#fff;">
                <input id="ai-data-consent" type="checkbox" value="1" style="margin-top:3px;">
                <span>Autorizo el uso de IA con mis datos para esta función. <a href="{{ route('legal.privacy', ['gym' => auth()->user()->gym?->invite_code]) }}" style="color:#fbbf24; text-decoration:underline;">Ver privacidad</a>.</span>
            </label>
        </div>
    @endunless

    <div class="chat-box">
        <div class="chat-messages" id="ai-chat-messages">
            <button type="button" id="ai-chat-load-older" class="link-btn" style="display:block; margin:0 auto 12px;" @if (! $hasOlder) hidden @endif>Ver mensajes anteriores</button>
            @forelse ($history as $m)
                <div class="chat-bubble-row {{ $m->role === 'user' ? 'mine' : '' }}" data-message-id="{{ $m->id }}">
                    <div class="chat-bubble">{{ $m->content }}</div>
                </div>
            @empty
                <div class="empty-text" style="text-align:center; margin-top:20px;">
                    Preguntame lo que quieras sobre tu rutina o tu dieta 💪
                </div>
            @endforelse
        </div>

        <form id="ai-chat-form" method="POST" action="{{ route('client.ai-chat.send') }}" class="chat-input-row">
            @csrf
            <div style="flex: 1; display: flex; flex-direction: column;">
                <input type="text" name="message" class="chat-input" placeholder="Escribí tu pregunta..." autocomplete="off" maxlength="1000" required>
                <span id="ai-chat-error" role="alert" style="color: #ef4444; font-size: 0.85em; margin-top: 4px;" hidden></span>
            </div>
            <button type="submit" class="chat-send-btn">Enviar</button>
        </form>
    </div>

        <form id="ai-chat-reset" method="POST" action="{{ route('client.ai-chat.reset') }}" style="margin-top:10px;" @if (! $history->count()) hidden @endif>
            @csrf
            <button type="submit" class="link-btn">🗑️ Borrar historial</button>
        </form>
</div>

<script>
    (() => {
        const messages = document.getElementById('ai-chat-messages');
        const form = document.getElementById('ai-chat-form');
        const input = form.elements.message;
        const button = form.querySelector('button');
        const error = document.getElementById('ai-chat-error');
        const consent = document.getElementById('ai-data-consent');
        const reset = document.getElementById('ai-chat-reset');
        const loadOlder = document.getElementById('ai-chat-load-older');
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        messages.scrollTop = messages.scrollHeight;

        function makeMessage(role, content, id) {
            const row = document.createElement('div');
            row.className = 'chat-bubble-row' + (role === 'user' ? ' mine' : '');
            row.dataset.messageId = id;
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble';
            bubble.textContent = content;
            row.appendChild(bubble);
            return row;
        }

        function appendMessage(role, content, id) {
            messages.querySelector('.empty-text')?.remove();
            messages.appendChild(makeMessage(role, content, id));
            messages.scrollTop = messages.scrollHeight;
        }

        loadOlder.addEventListener('click', async () => {
            const firstMessage = messages.querySelector('[data-message-id]');
            if (!firstMessage || loadOlder.disabled) return;
            loadOlder.disabled = true;
            error.hidden = true;
            const previousHeight = messages.scrollHeight;
            try {
                const url = new URL(@json(route('client.ai-chat.history')), window.location.origin);
                url.searchParams.set('before_id', firstMessage.dataset.messageId);
                const response = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.message || 'No se pudieron cargar los mensajes anteriores.');
                const fragment = document.createDocumentFragment();
                for (const message of data.messages) fragment.appendChild(makeMessage(message.role, message.content, message.id));
                messages.querySelector('.empty-text')?.remove();
                messages.insertBefore(fragment, messages.firstChild === loadOlder ? loadOlder.nextSibling : messages.firstChild);
                messages.scrollTop += messages.scrollHeight - previousHeight;
                loadOlder.hidden = !data.has_older;
            } catch (e) {
                error.textContent = e.message;
                error.hidden = false;
            } finally {
                loadOlder.disabled = false;
            }
        });

        async function post(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.errors?.ai_data_consent?.[0] || data.errors?.message?.[0] || data.message || (response.status === 419 ? 'Sesión vencida. Actualizá la página y volvé a intentar.' : 'No se pudo enviar el mensaje.'));
            }
            return data;
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const text = input.value.trim();
            if (!text || button.disabled) return;
            if (consent && !consent.checked) {
                error.textContent = 'Autorizá el envío de datos al proveedor de IA para usar este chat.';
                error.hidden = false;
                consent.focus();
                return;
            }
            error.hidden = true;
            button.disabled = true;
            button.textContent = 'Pensando…';
            try {
                const payload = { message: text };
                if (consent) payload.ai_data_consent = consent.checked ? '1' : '0';
                const data = await post(form.action, payload);
                if (data.ai_consent_saved) document.getElementById('ai-consent-note')?.remove();
                for (const message of data.messages) appendMessage(message.role, message.content, message.id);
                input.value = '';
                reset.hidden = false;
            } catch (e) {
                error.textContent = e.message;
                error.hidden = false;
            } finally {
                button.disabled = false;
                button.textContent = 'Enviar';
                input.focus();
            }
        });

        reset.addEventListener('submit', async event => {
            event.preventDefault();
            if (!confirm('¿Borrar todo el historial de esta conversación?')) return;
            try {
                await post(reset.action, {});
                messages.replaceChildren();
                loadOlder.hidden = true;
                reset.hidden = true;
                error.hidden = true;
            } catch (e) {
                error.textContent = e.message;
                error.hidden = false;
            }
        });
    })();
</script>

</x-layouts.client>
