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

        <form method="POST" action="{{ route('client.ai-chat.send') }}" class="chat-input-row">
            @csrf
            <input type="text" name="message" class="chat-input" placeholder="Escribí tu pregunta..." autocomplete="off" maxlength="1000" required>
            <button type="submit" class="chat-send-btn">Enviar</button>
        </form>
    </div>

    @if ($history->count())
        <form method="POST" action="{{ route('client.ai-chat.reset') }}" style="margin-top:10px;" onsubmit="return confirm('¿Borrar todo el historial de esta conversación?')">
            @csrf
            <button type="submit" class="link-btn">🗑️ Borrar historial</button>
        </form>
    @endif
</div>

<script>
    (function () {
        const el = document.getElementById('ai-chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    })();
</script>

</x-layouts.client>
