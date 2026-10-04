<x-layouts.client>

<div class="rw">
    <a class="back-link" href="{{ route('client.dashboard') }}">← Inicio</a>

    <p class="pg-label">Mensajes</p>
    <h1 class="pg-title">
        @if ($coach)
            Chat con {{ $coach->name }}
        @else
            Chat
        @endif
    </h1>

    @if (!$coach)
        <div class="empty">Todavía no tenés un coach asignado para chatear.</div>
    @else
        <div class="chat-box">
            <div class="chat-messages" id="chat-messages">
                @if ($messages->count() === 100)
                    <button type="button" id="load-older-messages" class="chat-send-btn" style="width:100%; margin-bottom:12px;">Cargar mensajes anteriores</button>
                @endif
                @forelse ($messages as $m)
                    <div class="chat-bubble-row {{ $m->sender_id === auth()->id() ? 'mine' : '' }}">
                        <div class="chat-bubble">
                            {{ $m->body }}
                            <div class="chat-bubble-time">{{ $m->created_at->format('H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-text" style="text-align:center; margin-top:20px;">Todavía no hay mensajes. ¡Escribí el primero!</div>
                @endforelse
            </div>

            <form id="chat-form" class="chat-input-row">
                <input type="text" id="chat-input" class="chat-input" placeholder="Escribí un mensaje..." autocomplete="off" maxlength="2000">
                <button type="submit" class="chat-send-btn">Enviar</button>
            </form>
        </div>
    @endif
</div>

@if ($coach)
<script>
(function () {
    const messagesEl = document.getElementById('chat-messages');
    const formEl = document.getElementById('chat-form');
    const inputEl = document.getElementById('chat-input');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let lastId = {{ $messages->max('id') ?? 0 }};
    let beforeId = {{ $messages->min('id') ?? 0 }};
    const loadOlderButton = document.getElementById('load-older-messages');

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    scrollToBottom();

    function renderMessage(m) {
        const row = document.createElement('div');
        row.className = 'chat-bubble-row' + (m.mine ? ' mine' : '');
        row.innerHTML = `<div class="chat-bubble">${escapeHtml(m.body)}<div class="chat-bubble-time">${m.time}</div></div>`;
        return row;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    async function poll() {
        try {
            const res = await fetch(`{{ route('client.chat.fetch') }}?after_id=${lastId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();

            if (data.messages && data.messages.length) {
                data.messages.forEach(m => {
                    messagesEl.appendChild(renderMessage(m));
                    lastId = m.id;
                });
                scrollToBottom();
            }
        } catch (e) {
            // silencioso: si falla un poll, probamos de nuevo en el siguiente ciclo
        }
    }

    setInterval(poll, 3000);

    loadOlderButton?.addEventListener('click', async function () {
        if (!beforeId) return;

        loadOlderButton.disabled = true;
        const previousHeight = messagesEl.scrollHeight;
        const previousTop = messagesEl.scrollTop;

        try {
            const res = await fetch(`{{ route('client.chat.fetch') }}?before_id=${beforeId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();

            if (data.messages?.length) {
                const fragment = document.createDocumentFragment();
                data.messages.forEach(message => fragment.appendChild(renderMessage(message)));
                messagesEl.insertBefore(fragment, loadOlderButton.nextSibling);
                beforeId = data.messages[0].id;
                messagesEl.scrollTop = previousTop + messagesEl.scrollHeight - previousHeight;
            }

            if (!data.has_older) loadOlderButton.remove();
        } catch (e) {
            loadOlderButton.disabled = false;
        }
    });

    formEl.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = inputEl.value.trim();
        if (!body) return;

        inputEl.value = '';
        inputEl.disabled = true;

        try {
            await fetch(`{{ route('client.chat.send') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ body }),
            });
            await poll();
        } finally {
            inputEl.disabled = false;
            inputEl.focus();
        }
    });
})();
</script>
@endif

</x-layouts.client>
