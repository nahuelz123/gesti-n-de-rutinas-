<x-filament-panels::page>
    <div style="display:flex; gap:1rem; height:70vh;">

        {{-- Lista de conversaciones --}}
        <div style="width:320px; flex-shrink:0; overflow-y:auto; border:1px solid rgb(39 39 42); border-radius:0.75rem;">
            @forelse ($this->conversations as $conv)
                <button
                    wire:click="selectConversation('{{ $conv['key'] }}')"
                    type="button"
                    style="width:100%; text-align:left; padding:0.75rem 1rem; border:none; border-bottom:1px solid rgb(39 39 42); cursor:pointer; background: {{ $selectedKey === $conv['key'] ? 'rgba(245,158,11,0.1)' : 'transparent' }}; color: {{ $selectedKey === $conv['key'] ? 'rgb(245,158,11)' : 'inherit' }};"
                >
                    <div style="font-weight:700; font-size:0.85rem;">
                        {{ $conv['coach']->name }} ↔ {{ $conv['client']->name }}
                    </div>
                    <div style="font-size:0.75rem; opacity:0.6; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $conv['last_message'] }}
                    </div>
                    <div style="font-size:0.65rem; opacity:0.4; margin-top:2px;">
                        {{ $conv['last_at']->diffForHumans() }} · {{ $conv['count'] }} mensajes
                    </div>
                </button>
            @empty
                <div style="padding:1rem; opacity:0.6; font-size:0.875rem;">Todavía no hay conversaciones en el gimnasio.</div>
            @endforelse
        </div>

        {{-- Hilo de mensajes (solo lectura) --}}
        <div style="flex:1; display:flex; flex-direction:column; border:1px solid rgb(39 39 42); border-radius:0.75rem; overflow:hidden;">
            @if (!$selectedKey)
                <div style="flex:1; display:flex; align-items:center; justify-content:center; opacity:0.5;">
                    Elegí una conversación para verla.
                </div>
            @else
                <div style="padding:0.75rem 1rem; border-bottom:1px solid rgb(39 39 42); font-size:0.75rem; opacity:0.6;">
                    👁️ Vista de solo lectura — este chat es entre el coach y el cliente.
                </div>

                <div style="flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:0.5rem;">
                    @forelse ($this->thread as $message)
                        @php $isStaff = in_array($message->sender->role, ['coach', 'admin', 'super_admin']); @endphp
                        <div style="display:flex; justify-content: {{ $isStaff ? 'flex-end' : 'flex-start' }};">
                            <div style="max-width:70%;">
                                <div style="font-size:0.65rem; opacity:0.5; margin-bottom:2px; text-align: {{ $isStaff ? 'right' : 'left' }};">
                                    {{ $message->sender->name }}
                                </div>
                                <div style="padding:0.5rem 0.75rem; border-radius:0.75rem; background: {{ $isStaff ? 'rgb(245,158,11)' : 'rgb(39 39 42)' }}; color: {{ $isStaff ? '#000' : '#fff' }}; font-size:0.875rem;">
                                    {{ $message->body }}
                                    <div style="font-size:0.65rem; opacity:0.6; margin-top:0.15rem;">
                                        {{ $message->created_at->format('d/m H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="opacity:0.5; text-align:center; margin-top:2rem;">Sin mensajes.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
