<div>
    @if ($step === 'overview')
        <div style="padding: var(--space-5) var(--space-4); max-width: 640px; margin: 0 auto; padding-bottom: calc(var(--space-6) + var(--bottom-nav-height));">
            <h1 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; color: var(--clr-text); margin-bottom: 2px;">
                TU RUTINA
            </h1>
            <p style="font-size: 14px; color: var(--clr-text-muted); font-weight: 500; margin-bottom: var(--space-6);">
                {{ $assignment->routine->title }}
            </p>

            <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                @foreach($assignment->routine->days as $day)
                    @php
                        $exCount = $day->exercises->count();
                        // Optional progress logic here if we load logs for all days, 
                        // but to avoid N+1 we just show the start button.
                    @endphp
                    <x-client.card>
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: var(--space-4);">
                            <div>
                                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--clr-primary); margin-bottom: 4px;">Día {{ $day->day_number }}</div>
                                <h3 style="font-size: 18px; font-weight: 700; color: var(--clr-text); margin-bottom: 2px;">{{ $day->title }}</h3>
                                <p style="font-size: 14px; color: var(--clr-text-muted);">{{ $exCount }} ejercicios</p>
                            </div>
                        </div>
                        
                        <x-client.action-button variant="primary" wire:click="selectDay({{ $day->id }})">
                            COMENZAR ENTRENAMIENTO
                        </x-client.action-button>
                    </x-client.card>
                @endforeach
            </div>
        </div>

    @elseif ($step === 'training')
        <style>
            /* Hide bottom nav during training */
            .client-bottom-nav { display: none !important; }
            .app-main { padding-bottom: 0 !important; }
            
            .workout-nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: var(--space-4);
                background: var(--clr-surface);
                border-bottom: 1px solid var(--clr-border);
                position: sticky;
                top: 0;
                z-index: 10;
            }
            .workout-progress-bar {
                height: 4px;
                background: rgba(255,255,255,0.1);
                border-radius: 2px;
                margin-top: 8px;
                overflow: hidden;
            }
            .workout-progress-fill {
                height: 100%;
                background: var(--clr-primary);
                transition: width 0.3s ease;
            }
            .set-row {
                background: var(--clr-surface);
                border: 1px solid var(--clr-border);
                border-radius: var(--radius-lg);
                padding: var(--space-4);
                margin-bottom: var(--space-3);
                transition: border-color 0.2s, transform 0.2s;
            }
            .set-row.completed {
                border-color: var(--clr-success);
                background: rgba(74, 222, 128, 0.03);
            }
            .set-number {
                font-size: 12px;
                font-weight: 700;
                color: var(--clr-text-muted);
                text-transform: uppercase;
                letter-spacing: 0.1em;
                margin-bottom: var(--space-3);
            }
            .set-controls {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: var(--space-3);
                margin-bottom: var(--space-4);
            }
            .set-control-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .set-control-label {
                font-size: 11px;
                color: var(--clr-text-muted);
                text-transform: uppercase;
                font-weight: 600;
                text-align: center;
            }
            .stepper {
                display: flex;
                align-items: center;
                background: var(--clr-bg);
                border-radius: var(--radius-md);
                border: 1px solid var(--clr-border);
                overflow: hidden;
                height: 44px;
            }
            .stepper-btn {
                width: 44px;
                height: 44px;
                background: transparent;
                border: none;
                color: var(--clr-text);
                font-size: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }
            .stepper-btn:active { background: rgba(255,255,255,0.05); }
            .stepper-input {
                flex: 1;
                width: 100%;
                background: transparent;
                border: none;
                color: var(--clr-text);
                font-size: 16px;
                font-weight: 700;
                text-align: center;
                padding: 0;
            }
            .stepper-input:focus { outline: none; }
            .stepper-input::placeholder { color: var(--clr-text-muted); }
            
            .tech-details {
                display: none;
                padding-top: var(--space-3);
                margin-top: var(--space-3);
                border-top: 1px dashed var(--clr-border);
                font-size: 14px;
                color: var(--clr-text-muted);
            }
            .tech-details.open { display: block; }
        </style>

        <div style="min-height: 100svh; background: var(--clr-bg); padding-bottom: 100px;">
            <div class="workout-nav">
                <button wire:click="exitTraining" wire:confirm="¿Salir del entrenamiento? Tu progreso guardado no se perderá." style="background:transparent; border:none; color:var(--clr-text); font-size:14px; font-weight:600; display:flex; align-items:center; gap:4px; padding:8px 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                    Salir
                </button>
                <div style="font-weight:700; font-size:14px;">{{ $this->day->title }}</div>
                <div style="font-size:14px; color:var(--clr-text-muted); font-weight:600;">{{ $currentExerciseIndex + 1 }} / {{ $this->exercises->count() }}</div>
            </div>
            
            <div style="padding: 0 var(--space-4);">
                <div class="workout-progress-bar">
                    <div class="workout-progress-fill" style="width: {{ (($currentExerciseIndex + 1) / $this->exercises->count()) * 100 }}%;"></div>
                </div>
            </div>

            @php $current = $this->currentExercise; @endphp
            @if($current)
                <div style="padding: var(--space-5) var(--space-4); max-width: 640px; margin: 0 auto;">
                    
                    {{-- Header Ejercicio --}}
                    <div style="margin-bottom: var(--space-6); text-align: center;">
                        <h2 style="font-size: 24px; font-weight: 800; color: var(--clr-text); margin-bottom: 8px;">{{ $current->exercise->title }}</h2>
                        <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,0.05); padding:6px 12px; border-radius:16px; font-size:13px; font-weight:600; color:var(--clr-text-muted);">
                            <span>{{ $current->sets }} series</span>
                            <span>&bull;</span>
                            <span>{{ $current->reps ?? '-' }} reps</span>
                            @if($current->rest)
                                <span>&bull;</span>
                                <span>{{ $current->rest }}s desc.</span>
                            @endif
                        </div>
                    </div>

                    {{-- Historial / Info --}}
                    @if($this->lastLog)
                        <div style="background: rgba(96, 165, 250, 0.08); border: 1px solid rgba(96, 165, 250, 0.2); border-radius: var(--radius-md); padding: var(--space-3); margin-bottom: var(--space-5); display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#60a5fa; text-transform:uppercase; margin-bottom:2px;">Última sesión</div>
                                <div style="font-size:14px; font-weight:600; color:var(--clr-text);">{{ rtrim(rtrim(number_format($this->lastLog->weight, 2, '.', ''), '0'), '.') }} kg × {{ $this->lastLog->reps }} reps</div>
                            </div>
                        </div>
                    @endif

                    {{-- Series --}}
                    <div style="display: flex; flex-direction: column;">
                        @for($i = 1; $i <= $current->sets; $i++)
                            @php $isCompleted = $this->isSetCompleted($i); @endphp
                            
                            <div class="set-row {{ $isCompleted ? 'completed' : '' }}" id="set-{{ $i }}">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                                    <div class="set-number">Serie {{ $i }}</div>
                                    @if($isCompleted)
                                        <div style="color:var(--clr-success); display:flex; align-items:center; gap:4px; font-size:12px; font-weight:700;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px; height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            COMPLETADA
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="set-controls">
                                    <div class="set-control-group">
                                        <div class="set-control-label">Peso (kg)</div>
                                        <div class="stepper">
                                            <button class="stepper-btn" wire:click="decreaseWeight({{ $i }})">−</button>
                                            <input type="text" inputmode="decimal" class="stepper-input" wire:model="inputs.{{ $i }}.weight" placeholder="0">
                                            <button class="stepper-btn" wire:click="increaseWeight({{ $i }})">+</button>
                                        </div>
                                    </div>
                                    <div class="set-control-group">
                                        <div class="set-control-label">Reps</div>
                                        <div class="stepper">
                                            <button class="stepper-btn" wire:click="decreaseReps({{ $i }})">−</button>
                                            <input type="text" inputmode="numeric" class="stepper-input" wire:model="inputs.{{ $i }}.reps" placeholder="0">
                                            <button class="stepper-btn" wire:click="increaseReps({{ $i }})">+</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    @error('set_' . $i) <span style="color:var(--clr-primary); font-size:12px; display:block; margin-bottom:8px;">{{ $message }}</span> @enderror
                                    <button wire:click="logSet({{ $i }})" class="client-btn {{ $isCompleted ? 'client-btn-secondary' : 'client-btn-primary' }}" style="width:100%; min-height:44px; font-size:14px;">
                                        @if($isCompleted)
                                            ACTUALIZAR SERIE
                                        @else
                                            ✓ COMPLETAR SERIE
                                        @endif
                                    </button>
                                </div>
                            </div>
                        @endfor
                    </div>

                    {{-- Next Prev Controls --}}
                    <div style="display: flex; gap: var(--space-3); margin-top: var(--space-5);">
                        @if($currentExerciseIndex > 0)
                            <button wire:click="prevExercise" class="client-btn client-btn-secondary" style="flex:1;">← Anterior</button>
                        @endif
                        <button wire:click="nextExercise" class="client-btn client-btn-secondary" style="flex:1;">
                            {{ $currentExerciseIndex < $this->exercises->count() - 1 ? 'Siguiente →' : 'Finalizar' }}
                        </button>
                    </div>

                </div>
            @endif

        </div>
        
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('set-logged', (event) => {
                    // Find the set row and animate it
                    const row = document.getElementById('set-' + event.set);
                    if(row) {
                        row.style.transform = 'scale(0.98)';
                        setTimeout(() => {
                            row.style.transform = 'scale(1)';
                        }, 150);
                    }
                });
                
                Livewire.on('exercise-changed', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        </script>

    @elseif ($step === 'completed')
        <div style="min-height: 100svh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-5); text-align: center;">
            <div style="width: 80px; height: 80px; background: rgba(74,222,128,0.1); color: var(--clr-success); border-radius: var(--radius-pill); display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-5);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:40px; height:40px;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </div>
            
            <h1 style="font-size: 24px; font-weight: 800; color: var(--clr-text); margin-bottom: 8px;">¡Entrenamiento Completado!</h1>
            <p style="font-size: 15px; color: var(--clr-text-muted); margin-bottom: var(--space-6); max-width: 280px;">
                Excelente trabajo. Completaste todos los ejercicios de {{ $this->day->title }}.
            </p>
            
            <a href="{{ route('client.dashboard') }}" style="text-decoration:none; width: 100%; max-width: 300px;">
                <x-client.action-button variant="primary">
                    VOLVER AL INICIO
                </x-client.action-button>
            </a>
        </div>
    @endif
</div>
