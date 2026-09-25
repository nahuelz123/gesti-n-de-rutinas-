<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    <p class="pg-label">Asistente virtual</p>
    <h1 class="pg-title">Preguntale a VisionFit AI</h1>

    <div class="chat-box">
        <div class="chat-messages" id="ai-chat-messages">
            @forelse ($history as $m)
                <div class="chat-bubble-row {{ $m->role === 'user' ? 'mine' : '' }}">
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
        const reset = document.getElementById('ai-chat-reset');
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        messages.scrollTop = messages.scrollHeight;

        function appendMessage(role, content) {
            messages.querySelector('.empty-text')?.remove();
            const row = document.createElement('div');
            row.className = 'chat-bubble-row' + (role === 'user' ? ' mine' : '');
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble';
            bubble.textContent = content;
            row.appendChild(bubble);
            messages.appendChild(row);
            messages.scrollTop = messages.scrollHeight;
        }

        async function post(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.errors?.message?.[0] || data.message || (response.status === 419 ? 'Sesión vencida. Actualizá la página y volvé a intentar.' : 'No se pudo enviar el mensaje.'));
            }
            return data;
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const text = input.value.trim();
            if (!text || button.disabled) return;
            error.hidden = true;
            button.disabled = true;
            button.textContent = 'Pensando…';
            try {
                const data = await post(form.action, { message: text });
                for (const message of data.messages) appendMessage(message.role, message.content);
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
