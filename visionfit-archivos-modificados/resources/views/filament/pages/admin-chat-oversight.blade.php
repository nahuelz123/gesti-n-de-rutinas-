<x-filament-panels::page>
    <style>
        .vfo-wrap { display: flex; flex-direction: column; gap: 16px; font-family: inherit; }
        @media (min-width: 768px) { .vfo-wrap { flex-direction: row; } }

        .vfo-sidebar {
            width: 100%; flex-shrink: 0;
            display: flex; flex-direction: column;
            border: 1px solid #27272a; border-radius: 12px; overflow: hidden;
            background: #0d0d0d;
        }
        @media (min-width: 768px) { .vfo-sidebar { width: 320px; } }

        .vfo-search-box { padding: 8px; border-bottom: 1px solid #27272a; }
        .vfo-search-input {
            width: 100%; box-sizing: border-box; background: #18181b; border: 1px solid #3f3f46;
            border-radius: 8px; padding: 9px 12px; font-size: 13px; color: #fff;
        }
        .vfo-search-input::placeholder { color: #71717a; }

        .vfo-conv-list { max-height: 220px; overflow-y: auto; }
        @media (min-width: 768px) { .vfo-conv-list { max-height: 65vh; } }

        .vfo-conv-btn {
            display: block; width: 100%; box-sizing: border-box; text-align: left;
            padding: 12px 16px; border: none; border-bottom: 1px solid #1f1f22;
            background: transparent; cursor: pointer; font-family: inherit;
        }
        .vfo-conv-btn:hover { background: #161616; }
        .vfo-conv-btn.active { background: rgba(230,57,70,0.12); }
        .vfo-conv-title { font-size: 13px; font-weight: 700; color: #f0f0f0; }
        .vfo-conv-btn.active .vfo-conv-title { color: #e63946; }
        .vfo-conv-preview { font-size: 11px; color: #a1a1aa; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vfo-conv-meta { font-size: 10px; color: #71717a; margin-top: 3px; }
        .vfo-empty { padding: 16px; font-size: 13px; color: #71717a; }

        .vfo-panel {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            border: 1px solid #27272a; border-radius: 12px; overflow: hidden;
            background: #0d0d0d;
            height: 65vh;
        }
        @media (min-width: 768px) { .vfo-panel { height: 70vh; } }

        .vfo-empty-state { flex: 1; display: flex; align-items: center; justify-content: center; text-align: center; padding: 20px; color: #71717a; font-size: 13px; }

        .vfo-readonly-banner { padding: 10px 16px; border-bottom: 1px solid #27272a; font-size: 11px; color: #a1a1aa; }

        .vfo-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .vfo-msg-row { display: flex; }
        .vfo-msg-row.staff { justify-content: flex-end; }
        .vfo-msg-row.client { justify-content: flex-start; }
        .vfo-msg-group { max-width: 82%; }
        @media (min-width: 640px) { .vfo-msg-group { max-width: 70%; } }
        .vfo-msg-sender { font-size: 10px; color: #71717a; margin-bottom: 2px; }
        .vfo-msg-row.staff .vfo-msg-sender { text-align: right; }
        .vfo-bubble { padding: 9px 13px; border-radius: 14px; font-size: 13px; }
        .vfo-msg-row.staff .vfo-bubble { background: #e63946; color: #fff; border-bottom-right-radius: 4px; }
        .vfo-msg-row.client .vfo-bubble { background: #27272a; color: #fff; border-bottom-left-radius: 4px; }
        .vfo-msg-time { font-size: 10px; opacity: 0.65; margin-top: 3px; }
    </style>

    <div class="vfo-wrap">

        {{-- Lista de conversaciones --}}
        <div class="vfo-sidebar">
            <div class="vfo-search-box">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="🔎 Buscar coach o cliente..."
                    class="vfo-search-input"
                    autocomplete="off"
                >
            </div>

            <div class="vfo-conv-list">
                @forelse ($this->conversations as $conv)
                    <button
                        wire:click="selectConversation('{{ $conv['key'] }}')"
                        type="button"
                        class="vfo-conv-btn {{ $selectedKey === $conv['key'] ? 'active' : '' }}"
                    >
                        <div class="vfo-conv-title">{{ $conv['coach']->name }} ↔ {{ $conv['client']->name }}</div>
                        <div class="vfo-conv-preview">{{ $conv['last_message'] }}</div>
                        <div class="vfo-conv-meta">{{ $conv['last_at']->diffForHumans() }} · {{ $conv['count'] }} mensajes</div>
                    </button>
                @empty
                    <div class="vfo-empty">No hay conversaciones que coincidan con la búsqueda.</div>
                @endforelse
            </div>
        </div>

        {{-- Hilo de mensajes (solo lectura) --}}
        <div class="vfo-panel">
            @if (!$selectedKey)
                <div class="vfo-empty-state">Elegí una conversación para verla.</div>
            @else
                <div class="vfo-readonly-banner">
                    👁️ Vista de solo lectura — este chat es entre el coach y el cliente.
                </div>

                <div class="vfo-messages">
                    @forelse ($this->thread as $message)
                        @php $isStaff = in_array($message->sender->role, ['coach', 'admin', 'super_admin']); @endphp
                        <div class="vfo-msg-row {{ $isStaff ? 'staff' : 'client' }}">
                            <div class="vfo-msg-group">
                                <div class="vfo-msg-sender">{{ $message->sender->name }}</div>
                                <div class="vfo-bubble">
                                    {{ $message->body }}
                                    <div class="vfo-msg-time">{{ $message->created_at->format('d/m H:i') }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="vfo-empty" style="text-align:center; margin-top:24px;">Sin mensajes.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
