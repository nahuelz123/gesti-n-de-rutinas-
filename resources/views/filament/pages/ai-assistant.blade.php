<x-filament-panels::page>
    <style>
        .vfai-wrap { display: flex; flex-direction: column; gap: 16px; font-family: inherit; }
        @media (min-width: 768px) { .vfai-wrap { flex-direction: row; } }

        .vfai-sidebar {
            width: 100%; flex-shrink: 0;
            display: flex; flex-direction: column;
            border: 1px solid #27272a; border-radius: 12px; overflow: hidden;
            background: #0d0d0d;
        }
        @media (min-width: 768px) { .vfai-sidebar { width: 260px; } }

        .vfai-search-box { padding: 8px; border-bottom: 1px solid #27272a; }
        .vfai-search-input {
            width: 100%; box-sizing: border-box; background: #18181b; border: 1px solid #3f3f46;
            border-radius: 8px; padding: 9px 12px; font-size: 13px; color: #fff;
        }
        .vfai-search-input::placeholder { color: #71717a; }

        .vfai-client-list { max-height: 220px; overflow-y: auto; }
        @media (min-width: 768px) { .vfai-client-list { max-height: 62vh; } }

        .vfai-client-btn {
            display: flex; align-items: center; gap: 8px;
            width: 100%; box-sizing: border-box; text-align: left;
            padding: 12px 16px; border: none; border-bottom: 1px solid #1f1f22;
            background: transparent; cursor: pointer; font-family: inherit;
        }
        .vfai-client-btn:hover { background: #161616; }
        .vfai-client-btn.active { background: rgba(230,57,70,0.12); }
        .vfai-client-name { font-size: 13px; font-weight: 700; color: #f0f0f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vfai-client-btn.active .vfai-client-name { color: #e63946; }
        .vfai-client-email { font-size: 11px; color: #71717a; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vfai-empty { padding: 16px; font-size: 13px; color: #71717a; }

        .vfai-panel {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            border: 1px solid #27272a; border-radius: 12px; overflow: hidden;
            background: #0d0d0d;
            height: 65vh;
        }
        @media (min-width: 768px) { .vfai-panel { height: 70vh; } }

        .vfai-header {
            display: flex; flex-direction: column; gap: 8px;
            padding: 12px 16px; border-bottom: 1px solid #27272a;
        }
        @media (min-width: 640px) { .vfai-header { flex-direction: row; align-items: center; justify-content: space-between; } }
        .vfai-header-hint { font-size: 11px; color: #71717a; }

        .vfai-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; }
        .vfai-messages.vfai-loading { opacity: 0.5; transition: opacity 0.15s; }
        .vfai-msg-row { display: flex; }
        .vfai-msg-row.mine { justify-content: flex-end; }
        .vfai-msg-row.theirs { justify-content: flex-start; }
        .vfai-bubble { max-width: 82%; padding: 9px 13px; border-radius: 14px; font-size: 13px; color: #fff; white-space: pre-wrap; }
        @media (min-width: 640px) { .vfai-bubble { max-width: 72%; } }
        .vfai-msg-row.mine .vfai-bubble { background: #e63946; border-bottom-right-radius: 4px; }
        .vfai-msg-row.theirs .vfai-bubble { background: #27272a; border-bottom-left-radius: 4px; }

        .vfai-tts-btn { display: block; margin-top: 5px; background: none; border: none; color: #a1a1aa; font-size: 11px; cursor: pointer; padding: 0; }
        .vfai-tts-btn:hover { color: #fff; }

        .vfai-thinking { font-size: 11px; color: #71717a; }

        .vfai-pending-card {
            border: 2px solid #e63946; background: rgba(230,57,70,0.06); border-radius: 12px;
            padding: 14px; margin-top: 6px;
        }
        .vfai-pending-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #e63946; margin-bottom: 6px; }
        .vfai-pending-title { font-size: 13px; font-weight: 700; color: #f0f0f0; margin-bottom: 4px; }
        .vfai-pending-subtitle { font-size: 13px; color: #d4d4d8; margin-bottom: 4px; }
        .vfai-pending-note { font-size: 11px; color: #71717a; margin-bottom: 12px; }
        .vfai-pending-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .vfai-form { display: flex; gap: 8px; padding: 12px; border-top: 1px solid #27272a; align-items: center; }
        .vfai-msg-input {
            flex: 1; min-width: 0; box-sizing: border-box; background: #18181b; border: 1px solid #3f3f46;
            border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #fff;
        }
        .vfai-msg-input::placeholder { color: #71717a; }
        .vfai-mic-btn {
            flex-shrink: 0; background: #27272a; border: 1px solid #3f3f46; color: #fff;
            border-radius: 8px; padding: 9px 12px; font-size: 15px; cursor: pointer;
        }
        .vfai-mic-status { padding: 0 12px 8px; font-size: 10px; color: #71717a; }
    </style>

    <div class="vfai-wrap">

        {{-- Lista de clientes --}}
        <div class="vfai-sidebar">
            <div class="vfai-search-box">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="clientSearch"
                    placeholder="🔎 Buscar cliente..."
                    class="vfai-search-input"
                    autocomplete="off"
                >
            </div>

            <div class="vfai-client-list">
                <button
                    wire:click="selectClient(null)"
                    type="button"
                    class="vfai-client-btn {{ $selectedClientId === null ? 'active' : '' }}"
                >
                    <span>💬</span>
                    <div>
                        <div class="vfai-client-name">Chat general</div>
                        <div class="vfai-client-email">Sin cliente puntual</div>
                    </div>
                </button>

                @forelse ($this->getClients() as $client)
                    <button
                        wire:click="selectClient({{ $client->id }})"
                        type="button"
                        class="vfai-client-btn {{ $selectedClientId === $client->id ? 'active' : '' }}"
                    >
                        <div style="min-width:0;">
                            <div class="vfai-client-name">{{ $client->name }}</div>
                            <div class="vfai-client-email">{{ $client->email }}</div>
                        </div>
                    </button>
                @empty
                    <div class="vfai-empty">No hay clientes que coincidan con la búsqueda.</div>
                @endforelse
            </div>
        </div>

        {{-- Chat --}}
        <div class="vfai-panel">
            <div class="vfai-header">
                <span class="vfai-header-hint">
                    @if ($selectedClientId === null)
                        Chat general: dale instrucciones a la IA (crear/editar rutinas, dietas, recetas). Siempre te va a pedir aprobación antes de guardar algo.
                    @else
                        Preguntale a la IA sobre el progreso, rutina o dieta de este cliente, o pedile que le arme/edite algo.
                    @endif
                </span>
                <x-filament::button color="danger" size="sm" wire:click="newChat" wire:confirm="¿Borrar todo este historial?">
                    🗑️ Vaciar historial
                </x-filament::button>
            </div>

            <div class="vfai-messages" id="ai-chat-scroll" wire:loading.class="vfai-loading" wire:target="send">
                @forelse ($history as $m)
                    @php $mine = $m->role === 'user'; @endphp
                    <div class="vfai-msg-row {{ $mine ? 'mine' : 'theirs' }}">
                        <div class="vfai-bubble">
                            {{ $m->content }}
                            @if (! $mine)
                                <button type="button" class="vfai-tts-btn ai-tts-btn" data-text="{{ $m->content }}" title="Escuchar">
                                    🔊 Escuchar
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="vfai-empty" style="text-align:center; margin-top:24px;">
                        @if ($selectedClientId === null)
                            Ej: "Creale una rutina de 3 días a Matías" o "Armá una receta de pollo al horno".
                        @else
                            Ej: "¿Cómo viene el progreso de fuerza este mes?" o "Armale una rutina nueva de empuje/tirón".
                        @endif
                    </div>
                @endforelse

                <div wire:loading wire:target="send" class="vfai-thinking">Pensando...</div>

                @if ($pendingAction)
                    <div class="vfai-pending-card">
                        <div class="vfai-pending-label">⏳ Propuesta pendiente de aprobación</div>

                        <div class="vfai-pending-title">
                            @switch($pendingAction['type'])
                                @case('routine_create') Rutina nueva @break
                                @case('routine_edit') Edición de rutina activa @break
                                @case('recipe_create') Receta nueva @break
                                @case('diet_plan_create') Plan de dieta nuevo @break
                                @case('diet_plan_edit') Edición de plan de dieta activo @break
                                @default Acción
                            @endswitch
                            @if ($pendingAction['client_name'])
                                — {{ $pendingAction['client_name'] }}
                            @endif
                        </div>

                        <div class="vfai-pending-subtitle">"{{ $pendingAction['args']['title'] ?? '' }}"</div>

                        <div class="vfai-pending-note">
                            Revisá el detalle en el mensaje de arriba. Si querés cambios, escribilos en el chat y la IA va a actualizar esta propuesta. Si está OK, aprobala para que se cree/asigne de verdad.
                        </div>

                        <div class="vfai-pending-actions">
                            <x-filament::button color="success" size="sm" wire:click="confirmPendingAction">
                                ✅ Aprobar y crear
                            </x-filament::button>
                            <x-filament::button color="gray" size="sm" wire:click="cancelPendingAction">
                                ✖️ Descartar
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>

            <form wire:submit="send" class="vfai-form">
                <input
                    type="text"
                    id="ai-message-input"
                    wire:model="message"
                    placeholder="{{ $selectedClientId === null ? 'Ej: creale una rutina de 3 días a...' : 'Escribí tu pregunta...' }}"
                    class="vfai-msg-input"
                    autocomplete="off"
                >
                <button type="button" id="ai-mic-btn" title="Dictar por voz" class="vfai-mic-btn">🎤</button>
                <x-filament::button type="submit">
                    Enviar
                </x-filament::button>
            </form>

            <div class="vfai-mic-status" id="ai-mic-status"></div>

            <script>
                (function () {
                    const micBtn = document.getElementById('ai-mic-btn');
                    const input = document.getElementById('ai-message-input');
                    const status = document.getElementById('ai-mic-status');
                    if (!micBtn || !input) return;

                    const SpeechRecognitionImpl = window.SpeechRecognition || window.webkitSpeechRecognition;

                    if (!SpeechRecognitionImpl) {
                        micBtn.style.display = 'none';
                        return;
                    }

                    let recognition = null;
                    let listening = false;

                    function setValue(text) {
                        input.value = text;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    micBtn.addEventListener('click', function () {
                        if (listening) {
                            recognition && recognition.stop();
                            return;
                        }

                        recognition = new SpeechRecognitionImpl();
                        recognition.lang = 'es-AR';
                        recognition.interimResults = true;
                        recognition.continuous = false;

                        recognition.onstart = function () {
                            listening = true;
                            micBtn.style.color = '#e63946';
                            micBtn.textContent = '⏺️';
                            status.textContent = 'Escuchando...';
                        };

                        recognition.onresult = function (event) {
                            let transcript = '';
                            for (let i = 0; i < event.results.length; i++) {
                                transcript += event.results[i][0].transcript;
                            }
                            setValue(transcript);
                        };

                        recognition.onerror = function (event) {
                            status.textContent = event.error === 'not-allowed'
                                ? 'Necesitás dar permiso de micrófono en el navegador.'
                                : 'No se pudo escuchar, probá de nuevo.';
                        };

                        recognition.onend = function () {
                            listening = false;
                            micBtn.style.color = '#fff';
                            micBtn.textContent = '🎤';
                            status.textContent = '';
                        };

                        recognition.start();
                    });
                })();

                (function () {
                    if (!window.speechSynthesis) return;

                    document.addEventListener('click', function (e) {
                        const btn = e.target.closest('.ai-tts-btn');
                        if (!btn) return;

                        window.speechSynthesis.cancel();
                        const utterance = new SpeechSynthesisUtterance(btn.dataset.text || '');
                        utterance.lang = 'es-AR';
                        window.speechSynthesis.speak(utterance);
                    });
                })();
            </script>
        </div>
    </div>

    <script>
        (function () {
            function scrollAiChatToBottom() {
                const el = document.getElementById('ai-chat-scroll');
                if (el) el.scrollTop = el.scrollHeight;
            }

            document.addEventListener('DOMContentLoaded', scrollAiChatToBottom);
            document.addEventListener('livewire:navigated', scrollAiChatToBottom);

            document.addEventListener('livewire:init', () => {
                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => requestAnimationFrame(scrollAiChatToBottom));
                });
            });
        })();
    </script>
</x-filament-panels::page>
