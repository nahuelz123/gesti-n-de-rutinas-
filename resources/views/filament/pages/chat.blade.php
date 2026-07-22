<x-filament-panels::page>
    <div wire:poll.4s="$refresh" class="fi-chat-wrapper" style="display:flex; gap:1rem; height:70vh;">

        {{-- Lista de clientes --}}
        <div style="width:260px; flex-shrink:0; overflow-y:auto; border:1px solid rgb(39 39 42); border-radius:0.75rem;">
            @forelse ($this->getClients() as $client)
                <button
                    wire:click="selectClient({{ $client->id }})"
                    type="button"
                    style="width:100%; text-align:left; padding:0.75rem 1rem; border:none; border-bottom:1px solid rgb(39 39 42); cursor:pointer; background: {{ $selectedClientId === $client->id ? 'rgba(245,158,11,0.1)' : 'transparent' }}; color: {{ $selectedClientId === $client->id ? 'rgb(245,158,11)' : 'inherit' }};"
                >
                    <div style="font-weight:700; font-size:0.875rem;">{{ $client->name }}</div>
                    <div style="font-size:0.75rem; opacity:0.6;">{{ $client->email }}</div>
                </button>
            @empty
                <div style="padding:1rem; opacity:0.6; font-size:0.875rem;">No hay clientes todavía.</div>
            @endforelse
        </div>

        {{-- Conversación --}}
        <div style="flex:1; display:flex; flex-direction:column; border:1px solid rgb(39 39 42); border-radius:0.75rem; overflow:hidden;">
            @if (!$selectedClientId)
                <div style="flex:1; display:flex; align-items:center; justify-content:center; opacity:0.5;">
                    Elegí un cliente para empezar a chatear.
                </div>
            @else
                <div style="flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:0.5rem;" id="chat-scroll">
                    @forelse ($this->messages as $message)
                        @php $mine = $message->sender_id === auth()->id(); @endphp
                        <div style="display:flex; justify-content: {{ $mine ? 'flex-end' : 'flex-start' }};">
                            <div style="max-width:70%; padding:0.5rem 0.75rem; border-radius:0.75rem; background: {{ $mine ? 'rgb(245,158,11)' : 'rgb(39 39 42)' }}; color: {{ $mine ? '#000' : '#fff' }}; font-size:0.875rem;">
                                {{ $message->body }}
                                <div style="font-size:0.65rem; opacity:0.6; margin-top:0.15rem;">
                                    {{ $message->created_at->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="opacity:0.5; text-align:center; margin-top:2rem;">Todavía no hay mensajes con este cliente.</div>
                    @endforelse
                </div>

                <form wire:submit="send" style="display:flex; gap:0.5rem; padding:0.75rem; border-top:1px solid rgb(39 39 42);">
                    <input
                        type="text"
                        wire:model="body"
                        placeholder="Escribí un mensaje..."
                        style="flex:1; background:rgb(24 24 27); border:1px solid rgb(63 63 70); border-radius:0.5rem; padding:0.5rem 0.75rem; color:#fff; font-size:0.875rem;"
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
