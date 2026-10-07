<div>
    @if ($step === 'overview')
        <style>
            .vf-routine-overview {
                max-width:760px;
                margin:0 auto;
                padding:24px 16px calc(96px + var(--safe-area-bottom));
            }
            .vf-routine-head { margin-bottom:22px; }
            .vf-routine-kicker {
                color:var(--clr-primary);
                font-size:10px;
                font-weight:800;
                letter-spacing:.12em;
                text-transform:uppercase;
                margin-bottom:6px;
            }
            .vf-routine-title {
                color:var(--clr-text);
                font-size:29px;
                line-height:1.05;
                font-weight:850;
                letter-spacing:-.04em;
            }
            .vf-routine-subtitle {
                margin-top:7px;
                color:var(--clr-text-muted);
                font-size:14px;
            }
            .vf-day-grid {
                display:grid;
                grid-template-columns:repeat(2,minmax(0,1fr));
                gap:12px;
            }
            .vf-day-card {
                border:1px solid var(--clr-border);
                border-radius:18px;
                background:linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.01)),var(--clr-surface);
                padding:18px;
            }
            .vf-day-number {
                color:var(--clr-primary);
                font-size:10px;
                font-weight:800;
                letter-spacing:.1em;
                text-transform:uppercase;
            }
            .vf-day-card h3 {
                margin-top:6px;
                color:var(--clr-text);
                font-size:18px;
                font-weight:800;
                letter-spacing:-.02em;
            }
            .vf-day-card p {
                margin:5px 0 16px;
                color:var(--clr-text-muted);
                font-size:13px;
            }
            @media(max-width:620px) {
                .vf-day-grid { grid-template-columns:1fr; }
                .vf-routine-title { font-size:26px; }
            }
        </style>

        <div class="vf-routine-overview">
            <header class="vf-routine-head">
                <div class="vf-routine-kicker">Tu entrenamiento</div>
                <h1 class="vf-routine-title">{{ $assignment->routine->title }}</h1>
                <p class="vf-routine-subtitle">Elegí el día que vas a entrenar y registrá cada serie a medida que avanzás.</p>
            </header>

            @if($assignment->status === 'completed' && $assignment->end_date && \Carbon\Carbon::parse($assignment->end_date)->isToday())
                <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(74,222,128,.10);color:var(--clr-success);font-size:12px;font-weight:750;padding:7px 10px;border:1px solid rgba(74,222,128,.18);border-radius:999px;margin-bottom:16px;">
                    Sesión reabierta desde el historial
                </div>
            @endif

            <div class="vf-day-grid">
                @foreach($assignment->routine->days as $day)
                    <article class="vf-day-card">
                        <div class="vf-day-number">Día {{ $day->day_number }}</div>
                        <h3>{{ $day->title }}</h3>
                        <p>{{ $day->exercises->count() }} ejercicios</p>

                        <x-client.action-button variant="primary" wire:click="selectDay({{ $day->id }})">
                            EMPEZAR
                        </x-client.action-button>
                    </article>
                @endforeach
            </div>
        </div>

    @elseif ($step === 'training')
        <style>
            .client-bottom-nav { display:none !important; }
            .app-main { padding-bottom:0 !important; }

            .vf-workout {
                min-height:100svh;
                background:var(--clr-bg);
                padding-bottom:96px;
            }
            .vf-workout-topbar {
                position:sticky;
                top:0;
                z-index:20;
                background:rgba(19,19,19,.94);
                backdrop-filter:blur(14px);
                border-bottom:1px solid var(--clr-border);
            }
            .vf-workout-topbar-inner {
                max-width:760px;
                margin:0 auto;
                padding:12px 16px 10px;
                display:grid;
                grid-template-columns:auto minmax(0,1fr) auto;
                align-items:center;
                gap:12px;
            }
            .vf-workout-exit {
                appearance:none;
                border:0;
                background:transparent;
                color:var(--clr-text-muted);
                font:inherit;
                font-size:13px;
                font-weight:700;
                padding:8px 0;
                cursor:pointer;
            }
            .vf-workout-day {
                color:var(--clr-text);
                font-size:13px;
                font-weight:800;
                text-align:center;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis;
            }
            .vf-workout-count {
                color:var(--clr-text-muted);
                font-size:12px;
                font-weight:700;
                font-variant-numeric:tabular-nums;
            }
            .vf-progress-track {
                max-width:760px;
                height:4px;
                margin:0 auto;
                background:rgba(255,255,255,.07);
                overflow:hidden;
            }
            .vf-progress-fill {
                height:100%;
                background:var(--clr-primary);
                transition:width .25s ease;
            }
            .vf-workout-body {
                max-width:760px;
                margin:0 auto;
                padding:22px 16px 0;
            }
            .vf-exercise-head {
                text-align:left;
                margin-bottom:18px;
            }
            .vf-exercise-kicker {
                color:var(--clr-primary);
                font-size:10px;
                font-weight:800;
                letter-spacing:.12em;
                text-transform:uppercase;
            }
            .vf-exercise-title {
                margin-top:6px;
                color:var(--clr-text);
                font-size:29px;
                line-height:1.08;
                font-weight:850;
                letter-spacing:-.04em;
            }
            .vf-prescription {
                display:flex;
                flex-wrap:wrap;
                gap:8px;
                margin-top:12px;
            }
            .vf-prescription span {
                display:inline-flex;
                align-items:center;
                min-height:30px;
                padding:0 10px;
                border-radius:999px;
                border:1px solid var(--clr-border);
                background:rgba(255,255,255,.025);
                color:var(--clr-text-muted);
                font-size:12px;
                font-weight:700;
            }
            .vf-exercise-actions {
                display:flex;
                gap:8px;
                margin-top:14px;
            }
            .vf-small-action {
                flex:1;
                min-height:42px;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:7px;
                border:1px solid var(--clr-border);
                border-radius:12px;
                background:var(--clr-surface);
                color:var(--clr-text);
                font-size:13px;
                font-weight:700;
                text-decoration:none;
                cursor:pointer;
            }
            .vf-last-log {
                margin:18px 0;
                padding:13px 14px;
                border-radius:14px;
                border:1px solid rgba(96,165,250,.18);
                background:rgba(96,165,250,.07);
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:12px;
            }
            .vf-last-log-label {
                color:#60a5fa;
                font-size:10px;
                font-weight:800;
                letter-spacing:.08em;
                text-transform:uppercase;
            }
            .vf-last-log-value {
                margin-top:3px;
                color:var(--clr-text);
                font-size:14px;
                font-weight:750;
            }

            .vf-set-list { display:flex; flex-direction:column; gap:10px; }
            .vf-set {
                border:1px solid var(--clr-border);
                border-radius:16px;
                background:var(--clr-surface);
                padding:14px;
                transition:border-color .2s ease, background .2s ease, transform .15s ease;
            }
            .vf-set.completed {
                border-color:rgba(74,222,128,.45);
                background:linear-gradient(180deg,rgba(74,222,128,.04),transparent),var(--clr-surface);
            }
            .vf-set-head {
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:10px;
                margin-bottom:12px;
            }
            .vf-set-index {
                color:var(--clr-text);
                font-size:13px;
                font-weight:800;
            }
            .vf-set-status {
                color:var(--clr-success);
                font-size:10px;
                font-weight:800;
                letter-spacing:.07em;
                text-transform:uppercase;
            }
            .vf-set-grid {
                display:grid;
                grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;
                gap:10px;
                align-items:end;
            }
            .vf-field label {
                display:block;
                margin-bottom:6px;
                color:var(--clr-text-muted);
                font-size:10px;
                font-weight:800;
                letter-spacing:.06em;
                text-transform:uppercase;
            }
            .vf-input-wrap {
                position:relative;
                display:flex;
                align-items:center;
                min-width:0;
                border:1px solid #303030;
                border-radius:12px;
                background:#0b0b0b;
                transition:border-color .15s ease, box-shadow .15s ease;
            }
            .vf-input-wrap:focus-within {
                border-color:var(--clr-primary);
                box-shadow:0 0 0 3px rgba(230,57,70,.10);
            }
            .vf-input {
                width:100%;
                min-width:0;
                height:50px;
                border:0;
                outline:0;
                background:transparent;
                color:var(--clr-text);
                text-align:center;
                font-size:20px;
                font-weight:800;
                font-variant-numeric:tabular-nums;
                padding:0 10px;
            }
            .vf-input::placeholder { color:#555; font-weight:600; }
            .vf-save {
                min-width:148px;
                height:50px;
                border:0;
                border-radius:12px;
                padding:0 16px;
                cursor:pointer;
                background:var(--clr-primary);
                color:#fff;
                font-size:12px;
                font-weight:850;
                letter-spacing:.03em;
            }
            .vf-set.completed .vf-save {
                background:var(--clr-surface-elevated);
                border:1px solid var(--clr-border);
                color:var(--clr-text);
            }
            .vf-use-last {
                margin-top:9px;
                border:0;
                background:transparent;
                color:#60a5fa;
                font-size:11px;
                font-weight:700;
                cursor:pointer;
                padding:0;
            }
            .vf-error {
                display:block;
                margin-top:7px;
                color:#fb7185;
                font-size:11px;
                line-height:1.35;
            }
            .vf-field-hint {
                display:block;
                margin-top:6px;
                color:#666;
                font-size:10px;
                line-height:1.3;
            }
            .vf-rest-timer {
                position:fixed;
                left:50%;
                bottom:18px;
                transform:translateX(-50%);
                width:min(440px, calc(100% - 32px));
                z-index:45;
                border:1px solid rgba(230,57,70,.32);
                border-radius:18px;
                background:rgba(19,19,19,.96);
                backdrop-filter:blur(16px);
                box-shadow:0 16px 44px rgba(0,0,0,.5);
                padding:12px 14px;
                display:flex;
                align-items:center;
                gap:12px;
            }
            .vf-rest-timer[hidden] { display:none; }
            .vf-rest-icon {
                width:38px;
                height:38px;
                border-radius:12px;
                display:grid;
                place-items:center;
                flex:0 0 38px;
                background:rgba(230,57,70,.12);
                color:var(--clr-primary);
                font-size:18px;
            }
            .vf-rest-copy { flex:1; min-width:0; }
            .vf-rest-label {
                color:var(--clr-text-muted);
                font-size:9px;
                font-weight:800;
                letter-spacing:.09em;
                text-transform:uppercase;
            }
            .vf-rest-time {
                margin-top:2px;
                color:var(--clr-text);
                font-size:20px;
                line-height:1;
                font-weight:850;
                font-variant-numeric:tabular-nums;
            }
            .vf-rest-skip {
                border:0;
                border-radius:10px;
                background:var(--clr-surface-elevated);
                color:var(--clr-text);
                font:inherit;
                font-size:11px;
                font-weight:800;
                padding:10px 12px;
                cursor:pointer;
            }
            .vf-workout-nav {
                display:flex;
                gap:10px;
                margin-top:18px;
            }
            .vf-workout-nav .client-btn {
                min-height:50px;
                border-radius:12px;
                font-weight:800;
            }

            @media(max-width:620px) {
                .vf-exercise-title { font-size:25px; }
                .vf-set-grid { grid-template-columns:1fr 1fr; }
                .vf-save { grid-column:1 / -1; width:100%; min-width:0; }
            }
            @media(max-width:390px) {
                .vf-set { padding:12px; }
                .vf-input { font-size:18px; }
                .vf-workout-topbar-inner { gap:8px; }
                .vf-workout-day { font-size:12px; }
            }
        </style>

        <div class="vf-workout">
            <div class="vf-workout-topbar">
                <div class="vf-workout-topbar-inner">
                    <button
                        class="vf-workout-exit"
                        wire:click="exitTraining"
                        wire:confirm="¿Salir del entrenamiento? Lo que ya guardaste no se perderá."
                    >
                        ← Salir
                    </button>

                    <div class="vf-workout-day">{{ $this->day->title }}</div>
                    <div class="vf-workout-count">{{ $currentExerciseIndex + 1 }}/{{ $this->exercises->count() }}</div>
                </div>

                <div class="vf-progress-track">
                    <div class="vf-progress-fill" style="width:{{ (($currentExerciseIndex + 1) / max(1, $this->exercises->count())) * 100 }}%"></div>
                </div>
            </div>

            @php
                $current = $this->currentExercise;
                $lastLog = $this->lastLog;
            @endphp

            @if($current)
                <main class="vf-workout-body">
                    <header class="vf-exercise-head">
                        <div class="vf-exercise-kicker">Ejercicio {{ $currentExerciseIndex + 1 }}</div>
                        <h1 class="vf-exercise-title">{{ $current->exercise->title }}</h1>

                        <div class="vf-prescription">
                            <span>{{ $current->sets }} series</span>
                            <span>Objetivo: {{ $current->reps ?? '-' }} reps</span>
                            @if($current->rest)
                                <span>{{ $current->rest }} s descanso</span>
                            @endif
                        </div>

                        <div class="vf-exercise-actions">
                            @if ($current->exercise->gif_url || $current->exercise->video_url)
                                @php
                                    $mediaType = $current->exercise->video_url ? 'video' : 'gif';
                                    $mediaUrl = $current->exercise->video_url ?? $current->exercise->gif_url;
                                @endphp
                                <button
                                    type="button"
                                    class="vf-small-action"
                                    x-data="{{ json_encode(['type' => $mediaType, 'url' => $mediaUrl, 'title' => $current->exercise->title]) }}"
                                    @click="$dispatch('open-tutorial', { type, url, title })"
                                >
                                    ▶ Tutorial
                                </button>
                            @endif

                            <a class="vf-small-action" href="{{ route('client.progress.exercise', $current->exercise_id) }}">
                                ↗ Progreso
                            </a>
                        </div>
                    </header>

                    @if($lastLog)
                        <div class="vf-last-log">
                            <div>
                                <div class="vf-last-log-label">Última marca de este ejercicio</div>
                                <div class="vf-last-log-value">
                                    @if($lastLog->weight === null || (float) $lastLog->weight <= 0)
                                        Peso corporal × {{ $lastLog->reps }} reps
                                    @else
                                        {{ rtrim(rtrim(number_format((float) $lastLog->weight, 2, '.', ''), '0'), '.') }} kg × {{ $lastLog->reps }} reps
                                    @endif
                                </div>
                            </div>
                            <div style="color:var(--clr-text-muted);font-size:11px;text-align:right;">
                                {{ optional($lastLog->logged_at)->format('d/m') }}<br>referencia
                            </div>
                        </div>
                    @endif

                    <div class="vf-set-list">
                        @for($i = 1; $i <= $current->sets; $i++)
                            @php $isCompleted = $this->isSetCompleted($i); @endphp

                            <section class="vf-set {{ $isCompleted ? 'completed' : '' }}" id="set-{{ $i }}">
                                <div class="vf-set-head">
                                    <div class="vf-set-index">Serie {{ $i }}</div>
                                    @if($isCompleted)
                                        <div class="vf-set-status">✓ Guardada</div>
                                    @endif
                                </div>

                                <div class="vf-set-grid">
                                    <div class="vf-field">
                                        <label for="weight-{{ $i }}">Peso (kg)</label>
                                        <div class="vf-input-wrap">
                                            <input
                                                id="weight-{{ $i }}"
                                                class="vf-input"
                                                type="text"
                                                inputmode="decimal"
                                                maxlength="7"
                                                autocomplete="off"
                                                wire:model="inputs.{{ $i }}.weight"
                                                aria-label="Peso en kg de la serie {{ $i }}"
                                                placeholder="0"
                                            >
                                        </div>
                                        <span class="vf-field-hint">0 o vacío = peso corporal</span>
                                        @error('inputs.' . $i . '.weight')
                                            <span class="vf-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="vf-field">
                                        <label for="reps-{{ $i }}">Repeticiones</label>
                                        <div class="vf-input-wrap">
                                            <input
                                                id="reps-{{ $i }}"
                                                class="vf-input"
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]{1,3}"
                                                maxlength="3"
                                                autocomplete="off"
                                                wire:model="inputs.{{ $i }}.reps"
                                                aria-label="Repeticiones de la serie {{ $i }}"
                                                placeholder="{{ is_numeric($current->reps) ? $current->reps : '0' }}"
                                            >
                                        </div>
                                        @error('inputs.' . $i . '.reps')
                                            <span class="vf-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="logSet({{ $i }})"
                                        class="vf-save"
                                        wire:loading.attr="disabled"
                                        wire:target="logSet({{ $i }})"
                                    >
                                        {{ $isCompleted ? 'ACTUALIZAR' : 'GUARDAR SERIE' }}
                                    </button>
                                </div>

                                @if($lastLog && !$isCompleted)
                                    <button type="button" class="vf-use-last" wire:click="useLastLog({{ $i }})">
                                        Usar última marca:
                                        @if($lastLog->weight === null || (float) $lastLog->weight <= 0)
                                            peso corporal
                                        @else
                                            {{ rtrim(rtrim(number_format((float) $lastLog->weight, 2, '.', ''), '0'), '.') }} kg
                                        @endif
                                        × {{ $lastLog->reps }}
                                    </button>
                                @endif

                                @error('set_' . $i)
                                    <span class="vf-error">{{ $message }}</span>
                                @enderror
                            </section>
                        @endfor
                    </div>

                    <div class="vf-workout-nav">
                        @if($currentExerciseIndex > 0)
                            <button wire:click="prevExercise" class="client-btn client-btn-secondary" style="flex:1;">← Anterior</button>
                        @endif

                        <button wire:click="nextExercise" class="client-btn client-btn-secondary" style="flex:1;">
                            {{ $currentExerciseIndex < $this->exercises->count() - 1 ? 'Siguiente →' : 'Finalizar' }}
                        </button>
                    </div>
                </main>
            @endif

            <div class="vf-rest-timer" id="vf-rest-timer" hidden aria-live="polite">
                <div class="vf-rest-icon">⏱</div>
                <div class="vf-rest-copy">
                    <div class="vf-rest-label">Descanso</div>
                    <div class="vf-rest-time" id="vf-rest-time">0:00</div>
                </div>
                <button type="button" class="vf-rest-skip" id="vf-rest-skip">OMITIR</button>
            </div>
        </div>

        <script>
            document.addEventListener('livewire:initialized', () => {
                if (window.__visionfitWorkoutListenersBound) return;
                window.__visionfitWorkoutListenersBound = true;

                const stopRestTimer = () => {
                    if (window.__visionfitRestInterval) {
                        clearInterval(window.__visionfitRestInterval);
                        window.__visionfitRestInterval = null;
                    }

                    const timer = document.getElementById('vf-rest-timer');
                    if (timer) timer.hidden = true;
                };

                const startRestTimer = (seconds) => {
                    const timer = document.getElementById('vf-rest-timer');
                    const time = document.getElementById('vf-rest-time');

                    if (!timer || !time || !Number.isFinite(seconds) || seconds <= 0) return;

                    stopRestTimer();
                    let remaining = Math.round(seconds);

                    const render = () => {
                        const minutes = Math.floor(remaining / 60);
                        const secs = String(remaining % 60).padStart(2, '0');
                        time.textContent = minutes + ':' + secs;
                        timer.hidden = false;

                        if (remaining <= 0) {
                            clearInterval(window.__visionfitRestInterval);
                            window.__visionfitRestInterval = null;
                            time.textContent = '¡Listo!';
                            if ('vibrate' in navigator) navigator.vibrate([160, 80, 160]);
                            setTimeout(() => {
                                const current = document.getElementById('vf-rest-timer');
                                if (current) current.hidden = true;
                            }, 2200);
                            return;
                        }

                        remaining--;
                    };

                    render();
                    window.__visionfitRestInterval = setInterval(render, 1000);
                };

                document.addEventListener('click', (event) => {
                    if (event.target?.id === 'vf-rest-skip') stopRestTimer();
                });

                Livewire.on('set-logged', (event) => {
                    const payload = event?.[0] ?? event ?? {};
                    const setNumber = payload.set;
                    const row = document.getElementById('set-' + setNumber);

                    if (row) {
                        row.style.transform = 'scale(.99)';
                        setTimeout(() => row.style.transform = 'scale(1)', 140);
                    }

                    startRestTimer(Number(payload.rest || 0));
                });

                Livewire.on('exercise-changed', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        </script>

    @elseif ($step === 'completed')
        <div style="min-height:100svh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;text-align:center;">
            <div style="width:76px;height:76px;border-radius:24px;display:grid;place-items:center;background:rgba(74,222,128,.10);color:var(--clr-success);border:1px solid rgba(74,222,128,.18);margin-bottom:20px;">
                <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </div>

            <h1 style="font-size:27px;font-weight:850;letter-spacing:-.04em;color:var(--clr-text);margin-bottom:8px;">
                Entrenamiento completado
            </h1>
            <p style="font-size:14px;line-height:1.55;color:var(--clr-text-muted);margin-bottom:24px;max-width:320px;">
                Listo. Tus series quedaron registradas y ya podés seguir comparando tu progreso en la próxima sesión.
            </p>

            @if($assignment->status === 'completed' && $assignment->end_date && \Carbon\Carbon::parse($assignment->end_date)->isToday())
                <a href="{{ route('client.routines.history') }}" style="text-decoration:none;width:100%;max-width:320px;">
                    <x-client.action-button variant="primary">VOLVER AL HISTORIAL</x-client.action-button>
                </a>
            @else
                <a href="{{ route('client.dashboard') }}" style="text-decoration:none;width:100%;max-width:320px;">
                    <x-client.action-button variant="primary">VOLVER AL INICIO</x-client.action-button>
                </a>
            @endif
        </div>
    @endif

    <div
        x-data="{ open: false, type: '', url: '', title: '' }"
        @open-tutorial.window="open = true; type = $event.detail.type; url = $event.detail.url; title = $event.detail.title"
        x-show="open"
        style="display:none;"
        class="modal-overlay"
        :class="{ 'open': open }"
        @click.self="open = false; url = ''"
    >
        <div class="modal-box" style="background:var(--clr-surface);border:1px solid var(--clr-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--clr-border);">
                <span class="modal-title" x-text="title" style="color:var(--clr-text);"></span>
                <button class="modal-close" @click="open = false; url = ''" style="color:var(--clr-text-muted);">×</button>
            </div>
            <div class="modal-body" style="padding:0;background:#000;">
                <template x-if="type === 'video' && url">
                    <iframe
                        :src="'https://www.youtube.com/embed/' + (url.match(/(?:v=|youtu\.be\/)([^&?\/]+)/) ? url.match(/(?:v=|youtu\.be\/)([^&?\/]+)/)[1] : '') + '?autoplay=1'"
                        allowfullscreen
                        style="width:100%;aspect-ratio:16/9;border:none;display:block;"
                    ></iframe>
                </template>

                <template x-if="type === 'gif' && url">
                    <img :src="url" style="width:100%;max-height:70vh;object-fit:contain;display:block;margin:0 auto;" alt="">
                </template>
            </div>
        </div>
    </div>
</div>
