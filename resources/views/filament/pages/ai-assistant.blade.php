<x-filament-panels::page>
    <div class="flex flex-col md:flex-row gap-4">

        {{-- Lista de clientes --}}
        <div class="w-full md:w-64 flex-shrink-0 max-h-48 md:max-h-[70vh] overflow-y-auto border rounded-xl" style="border-color: rgb(39 39 42);">
            @forelse ($this->getClients() as $client)
                <button
                    wire:click="selectClient({{ $client->id }})"
                    type="button"
                    class="w-full text-left px-4 py-3 border-b"
                    style="border-color: rgb(39 39 42); background: {{ $selectedClientId === $client->id ? 'rgba(245,158,11,0.1)' : 'transparent' }}; color: {{ $selectedClientId === $client->id ? 'rgb(245,158,11)' : 'inherit' }};"
                >
                    <div class="font-bold text-sm">{{ $client->name }}</div>
                    <div class="text-xs opacity-60">{{ $client->email }}</div>
                </button>
            @empty
                <div class="p-4 text-sm opacity-60">No hay clientes todavía.</div>
            @endforelse
        </div>

        {{-- Chat --}}
        <div class="flex-1 flex flex-col border rounded-xl overflow-hidden h-[65vh] md:h-[70vh]" style="border-color: rgb(39 39 42);">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 px-4 py-3 border-b" style="border-color: rgb(39 39 42);">
                <span class="text-xs opacity-60">
                    Preguntale a la IA sobre el progreso, rutina o dieta de este cliente.
                </span>
                <x-filament::button color="danger" size="sm" wire:click="newChat" wire:confirm="¿Borrar todo el historial con este cliente?">
                    🗑️ Vaciar historial
                </x-filament::button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-2" wire:loading.class="opacity-50" wire:target="send">
                @forelse ($history as $m)
                    @php $mine = $m->role === 'user'; @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] sm:max-w-[75%] px-3 py-2 rounded-xl text-sm whitespace-pre-wrap" style="background: {{ $mine ? 'rgb(245,158,11)' : 'rgb(39 39 42)' }}; color: {{ $mine ? '#000' : '#fff' }};">
                            {{ $m->content }}
                        </div>
                    </div>
                @empty
                    <div class="opacity-50 text-center mt-8 text-sm px-2">
                        Ej: "¿Cómo viene el progreso de fuerza este mes?" o "Sugerime ajustes a su dieta".
                    </div>
                @endforelse

                <div wire:loading wire:target="send" class="opacity-50 text-xs">
                    Pensando...
                </div>
            </div>

            <form wire:submit="send" class="flex gap-2 p-3 border-t" style="border-color: rgb(39 39 42);">
                <input
                    type="text"
                    wire:model="message"
                    placeholder="Escribí tu pregunta..."
                    class="flex-1 min-w-0 rounded-lg px-3 py-2 text-sm"
                    style="background:rgb(24 24 27); border:1px solid rgb(63 63 70); color:#fff;"
                    autocomplete="off"
                >
                <x-filament::button type="submit">
                    Enviar
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page>
