<x-filament-panels::page>
    <style>
        .vfc-wrap { display: flex; flex-direction: column; gap: 16px; font-family: inherit; }
        @media (min-width: 768px) { .vfc-wrap { flex-direction: row; } }

        .vfc-sidebar {
            width: 100%; flex-shrink: 0;
            display: flex; flex-direction: column;
            border: 1px solid #27272a; border-radius: 12px; overflow: hidden;
            background: #0d0d0d;
        }
        @media (min-width: 768px) { .vfc-sidebar { width: 260px; } }

        .vfc-search-box { padding: 8px; border-bottom: 1px solid #27272a; }
        .vfc-search-input {
            width: 100%; box-sizing: border-box; background: #18181b; border: 1px solid #3f3f46;
            border-radius: 8px; padding: 9px 12px; font-size: 13px; color: #fff;
        }
        .vfc-search-input::placeholder { color: #71717a; }

        .vfc-client-list { max-height: 220px; overflow-y: auto; }
        @media (min-width: 768px) { .vfc-client-list { max-height: 65vh; } }

        .vfc-client-btn {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            width: 100%; box-sizing: border-box; text-align: left;
            padding: 12px 16px; border: none; border-bottom: 1px solid #1f1f22;
            background: transparent; cursor: pointer; font-family: inherit;
        }
        .vfc-client-btn:hover { background: #161616; }
        .vfc-client-btn.active { background: rgba(230,57,70,0.12); }
        .vfc-client-name { font-size: 13px; font-weight: 700; color: #f0f0f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vfc-client-btn.active .vfc-client-name { color: #e63946; }
        .vfc-client-email { font-size: 11px; color: #71717a; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vfc-unread-badge {
            flex-shrink: 0; background: #e63946; color: #fff; font-size: 10px; font-weight: 800;
            border-radius: 100px; padding: 2px 7px;
        }
        .vfc-empty { padding: 16px; font-size: 13px; color: #71717a; }

        .vfc-panel {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            border: 1px solid #27272a; border-radius: 12px; overflow: hidden;
            background: #0d0d0d;
            height: 65vh;
        }
        @media (min-width: 768px) { .vfc-panel { height: 70vh; } }

        .vfc-empty-state { flex: 1; display: flex; align-items: center; justify-content: center; text-align: center; padding: 20px; color: #71717a; font-size: 13px; }

        .vfc-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; }
        .vfc-msg-row { display: flex; }
        .vfc-msg-row.mine { justify-content: flex-end; }
        .vfc-msg-row.theirs { justify-content: flex-start; }
        .vfc-bubble { max-width: 78%; padding: 9px 13px; border-radius: 14px; font-size: 13px; color: #fff; }
        .vfc-msg-row.mine .vfc-bubble { background: #e63946; border-bottom-right-radius: 4px; }
        .vfc-msg-row.theirs .vfc-bubble { background: #27272a; border-bottom-left-radius: 4px; }
        .vfc-msg-time { font-size: 10px; opacity: 0.65; margin-top: 3px; }

        .vfc-form { display: flex; gap: 8px; padding: 12px; border-top: 1px solid #27272a; }
        .vfc-msg-input {
            flex: 1; min-width: 0; box-sizing: border-box; background: #18181b; border: 1px solid #3f3f46;
            border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #fff;
        }
        .vfc-msg-input::placeholder { color: #71717a; }
    </style>

    <div wire:poll.4s="$refresh" class="vfc-wrap">

        {{-- Lista de clientes --}}
        <div class="vfc-sidebar">
            <div class="vfc-search-box">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="clientSearch"
                    placeholder="🔎 Buscar cliente..."
                    class="vfc-search-input"
                    autocomplete="off"
                >
            </div>

            <div class="vfc-client-list">
                @forelse ($this->getClients() as $client)
                    @php $unread = $this->unreadCounts[$client->id] ?? 0; @endphp
                    <button
                        wire:click="selectClient({{ $client->id }})"
                        type="button"
                        class="vfc-client-btn {{ $selectedClientId === $client->id ? 'active' : '' }}"
                    >
                        <div style="min-width:0;">
                            <div class="vfc-client-name">{{ $client->name }}</div>
                            <div class="vfc-client-email">{{ $client->email }}</div>
                        </div>
                        @if ($unread > 0)
                            <span class="vfc-unread-badge">{{ $unread }}</span>
                        @endif
                    </button>
                @empty
                    <div class="vfc-empty">No hay clientes que coincidan con la búsqueda.</div>
                @endforelse
            </div>
        </div>

        {{-- Conversación --}}
        <div class="vfc-panel">
            @if (!$selectedClientId)
                <div class="vfc-empty-state">Elegí un cliente para empezar a chatear.</div>
            @else
                <div class="vfc-messages" id="chat-scroll">
                    @forelse ($this->messages as $message)
                        @php $mine = $message->sender_id === auth()->id(); @endphp
                        <div class="vfc-msg-row {{ $mine ? 'mine' : 'theirs' }}">
                            <div class="vfc-bubble">
                                {{ $message->body }}
                                <div class="vfc-msg-time">{{ $message->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="vfc-empty" style="text-align:center; margin-top:24px;">Todavía no hay mensajes con este cliente.</div>
                    @endforelse
                </div>

                <form wire:submit="send" class="vfc-form">
                    <input
                        type="text"
                        wire:model="body"
                        placeholder="Escribí un mensaje..."
                        class="vfc-msg-input"
                        autocomplete="off"
                    >
                    <x-filament::button type="submit">
                        Enviar
                    </x-filament::button>
                </form>
            @endif
        </div>
    </div>
</x-filament-panels::page>
