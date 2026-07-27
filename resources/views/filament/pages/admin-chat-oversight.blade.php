<x-filament-panels::page>
    <div class="flex flex-col md:flex-row gap-4">

        {{-- Lista de conversaciones --}}
        <div class="w-full md:w-80 flex-shrink-0 max-h-48 md:max-h-[70vh] overflow-y-auto border rounded-xl" style="border-color: rgb(39 39 42);">
            @forelse ($this->conversations as $conv)
                <button
                    wire:click="selectConversation('{{ $conv['key'] }}')"
                    type="button"
                    class="w-full text-left px-4 py-3 border-b"
                    style="border-color: rgb(39 39 42); background: {{ $selectedKey === $conv['key'] ? 'rgba(245,158,11,0.1)' : 'transparent' }}; color: {{ $selectedKey === $conv['key'] ? 'rgb(245,158,11)' : 'inherit' }};"
                >
                    <div class="font-bold text-sm">
                        {{ $conv['coach']->name }} ↔ {{ $conv['client']->name }}
                    </div>
                    <div class="text-xs opacity-60 mt-0.5 truncate">
                        {{ $conv['last_message'] }}
                    </div>
                    <div class="text-[10px] opacity-40 mt-0.5">
                        {{ $conv['last_at']->diffForHumans() }} · {{ $conv['count'] }} mensajes
                    </div>
                </button>
            @empty
                <div class="p-4 text-sm opacity-60">Todavía no hay conversaciones en el gimnasio.</div>
            @endforelse
        </div>

        {{-- Hilo de mensajes (solo lectura) --}}
        <div class="flex-1 flex flex-col border rounded-xl overflow-hidden h-[65vh] md:h-[70vh]" style="border-color: rgb(39 39 42);">
            @if (!$selectedKey)
                <div class="flex-1 flex items-center justify-center opacity-50 text-sm px-4 text-center">
                    Elegí una conversación para verla.
                </div>
            @else
                <div class="px-4 py-3 border-b text-xs opacity-60" style="border-color: rgb(39 39 42);">
                    👁️ Vista de solo lectura — este chat es entre el coach y el cliente.
                </div>

                <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-2">
                    @forelse ($this->thread as $message)
                        @php $isStaff = in_array($message->sender->role, ['coach', 'admin', 'super_admin']); @endphp
                        <div class="flex {{ $isStaff ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] sm:max-w-[70%]">
                                <div class="text-[10px] opacity-50 mb-0.5 {{ $isStaff ? 'text-right' : 'text-left' }}">
                                    {{ $message->sender->name }}
                                </div>
                                <div class="px-3 py-2 rounded-xl text-sm" style="background: {{ $isStaff ? 'rgb(245,158,11)' : 'rgb(39 39 42)' }}; color: {{ $isStaff ? '#000' : '#fff' }};">
                                    {{ $message->body }}
                                    <div class="text-[10px] opacity-60 mt-0.5">
                                        {{ $message->created_at->format('d/m H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="opacity-50 text-center mt-8 text-sm">Sin mensajes.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
