<x-layouts.client :is-demo="$isDemo">
    <style>
        .vf-activity{max-width:1080px;margin:auto;padding:24px 16px 110px;color:var(--clr-text)}
        .vf-activity h1{font-size:29px;font-weight:800;line-height:1.2;margin:14px 0 8px}.vf-activity h2{font-size:19px;font-weight:750;margin-bottom:14px}
        .vf-activity p{line-height:1.65}.vf-activity a{color:var(--clr-primary)}
        .vf-activity-muted{color:var(--clr-text-muted);font-size:13px}.vf-activity-panel{padding:20px;border:1px solid var(--clr-border);background:var(--clr-surface);border-radius:18px;margin-top:18px;min-width:0}
        .vf-activity-summaries{display:grid;grid-template-columns:1fr 1fr;gap:16px}.vf-activity-total{font-size:34px;font-weight:800;line-height:1.2;margin:12px 0}.vf-activity-total small{font-size:13px;font-weight:500;color:var(--clr-text-muted)}
        .vf-activity-breakdown{display:flex;flex-wrap:wrap;gap:12px 24px;font-size:13px}.vf-activity-breakdown strong{display:block;font-size:16px;margin-top:4px}
        .vf-activity-form{display:grid;grid-template-columns:1fr 1fr;gap:16px}.vf-activity-form .wide{grid-column:1/-1}
        .vf-activity label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}.vf-activity input,.vf-activity select,.vf-activity textarea{display:block;width:100%;min-width:0;min-height:48px;padding:12px;border:1px solid var(--clr-border);border-radius:11px;background:var(--clr-surface-elevated);color:var(--clr-text);font:inherit;font-size:16px}
        .vf-activity textarea{min-height:82px;resize:vertical}.vf-activity input:focus,.vf-activity select:focus,.vf-activity textarea:focus{outline:2px solid var(--clr-primary);outline-offset:2px}
        .vf-activity label.vf-activity-check{display:flex;align-items:flex-start;gap:12px;font-weight:500;line-height:1.6}.vf-activity-check input[type=checkbox]{width:22px;height:22px;min-height:22px;flex:0 0 22px;margin-top:2px;accent-color:var(--clr-primary)}
        .vf-activity .client-btn{min-height:48px;width:auto;padding:12px 18px;text-align:center;white-space:normal}.vf-activity .client-btn-primary{color:white}.vf-activity .client-btn:disabled{opacity:.55;cursor:default}
        .vf-activity-date-filter{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-top:20px}.vf-activity-date-filter>div{flex:1;max-width:240px}
        .vf-activity-error{color:#ff8992;font-size:13px;margin-top:6px}.vf-activity-notice{padding:14px;border:1px solid var(--clr-border);border-radius:12px;margin:16px 0;background:var(--clr-surface-elevated);font-size:13px;line-height:1.6}
        .vf-activity-log{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:17px 0;border-top:1px solid var(--clr-border)}.vf-activity-log>div{min-width:0}.vf-activity-log h3{font-size:16px;font-weight:700;margin-bottom:7px}.vf-activity-log p{overflow-wrap:anywhere}.vf-activity-log-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:12px}.vf-activity-log-actions a,.vf-activity-log-actions button{min-height:44px;display:inline-flex;align-items:center;padding:8px 12px;border:1px solid var(--clr-border);border-radius:9px;font-size:13px;background:transparent;color:var(--clr-text)}
        .vf-activity-week{width:100%;border-collapse:collapse;font-size:13px}.vf-activity-week th,.vf-activity-week td{padding:12px 6px;border-bottom:1px solid var(--clr-border);text-align:right}.vf-activity-week th:first-child,.vf-activity-week td:first-child{text-align:left}.vf-activity-week th{color:var(--clr-text-muted);font-weight:600}.vf-activity details summary{cursor:pointer;min-height:44px;display:flex;align-items:center;font-size:14px;font-weight:700}
        @media(max-width:640px){.vf-activity-summaries,.vf-activity-form{grid-template-columns:minmax(0,1fr)}.vf-activity-panel{padding:17px}.vf-activity-log{flex-direction:column;gap:10px}.vf-activity-date-filter .client-btn{flex:1}.vf-activity-week{font-size:12px}.vf-activity-week th,.vf-activity-week td{padding:12px 3px}}
    </style>
    <div class="vf-activity">
        <a href="{{ route($isDemo ? 'experience.demo' : 'client.dashboard') }}">← Inicio</a>
        <h1>Cardio y gasto</h1>
        <p class="vf-activity-muted">Sumá cardio y pasos. Las series de fuerza de tu rutina ya aportan una estimación si tenés tu peso registrado.</p>
        @if($isDemo)<p class="vf-activity-notice"><strong>Modo demo · Datos ficticios.</strong> El ejemplo usa 80 kg, 30 minutos de elíptico moderado y 60 minutos de fuerza habitual. Para guardar tu actividad, <a href="{{ route('login') }}">iniciá sesión</a>.</p>@endif
        @if(session('success'))<p role="status" class="vf-activity-notice">{{ session('success') }}</p>@endif
        @if($errors->any())<div role="alert" class="vf-activity-notice"><strong>Revisá los datos antes de guardar.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <form method="GET" action="{{ route($isDemo ? 'experience.demo.activity' : 'client.activities.index') }}" class="vf-activity-date-filter">
            <div><label for="activity-filter-date">Día a consultar</label><input id="activity-filter-date" type="date" name="date" value="{{ $selectedDate }}" max="{{ today()->toDateString() }}" min="2000-01-01" required @disabled($isDemo)></div>
            <button class="client-btn client-btn-secondary" type="submit" @disabled($isDemo)>Ver resumen</button>
        </form>
        <div class="vf-activity-summaries">
            @foreach(['Día seleccionado' => $daySummary, 'Semana del '.\Carbon\CarbonImmutable::parse(array_key_first($weekDays))->format('d/m').' al '.\Carbon\CarbonImmutable::parse(array_key_last($weekDays))->format('d/m') => $weekSummary] as $title => $summary)
                <section class="vf-activity-panel" aria-label="{{ $title }}">
                    <h2>{{ $title }}</h2><p class="vf-activity-muted">Estimación de calorías activas</p>
                    <div class="vf-activity-total">@if($summary['total_kcal'] == 0 && $summary['unestimated_strength_sets'])Pendiente @else≈ {{ number_format($summary['total_kcal'], 0, ',', '.') }} <small>kcal</small>@endif</div>
                    <div class="vf-activity-breakdown"><div>Cardio y pasos · {{ $summary['cardio_minutes'] }} min<strong>≈ {{ number_format($summary['cardio_kcal'], 0, ',', '.') }} kcal</strong></div><div>Fuerza · {{ $summary['strength_minutes'] }} min<strong>≈ {{ number_format($summary['strength_kcal'], 0, ',', '.') }} kcal</strong></div></div>
                    @if($summary['steps'])<p class="vf-activity-muted" style="margin-top:12px;">Pasos adicionales: {{ number_format($summary['steps'], 0, ',', '.') }}. Su gasto ya está incluido en cardio.</p>@endif
                    @if($summary['estimated_strength_sets'])<p class="vf-activity-muted" style="margin-top:12px;"><strong>Estimación desde tus series:</strong> {{ $summary['estimated_strength_sets'] }} series, con tiempo aproximado de trabajo y descanso. Podés cargar el tiempo real total de fuerza del día para reemplazarla.</p>@endif
                    @if($summary['unestimated_strength_sets'])<p class="vf-activity-muted" style="margin-top:12px;">Hay {{ $summary['unestimated_strength_sets'] }} series sin cálculo porque falta un peso corporal para esa fecha. <a href="{{ route('client.progress.index') }}#measurement-form">Registrar peso</a>.</p>@endif
                    @if(!$summary['record_count'] && !$summary['estimated_strength_sets'] && !$summary['unestimated_strength_sets'])<p class="vf-activity-muted" style="margin-top:12px;">Sin actividades ni series de fuerza registradas para este período.</p>@endif
                </section>
            @endforeach
        </div>
        <p class="vf-activity-muted" style="margin-top:14px;">El resumen incluye actividades cargadas y fuerza estimada desde tus series. No es tu gasto total del día y no modifica tus objetivos de alimentación.</p>

        <section class="vf-activity-panel" aria-labelledby="activity-form-heading">
            <h2 id="activity-form-heading">{{ $editing ? 'Editar actividad' : 'Registrar cardio, pasos o tiempo de fuerza' }}</h2>
            <p class="vf-activity-muted" style="margin-bottom:18px;">Si cargás fuerza a mano, usá el tiempo total del día con descansos habituales, excluyendo cardio y pausas largas. Ese tiempo reemplaza la estimación automática del mismo día.</p>
            <form method="POST" action="{{ $editing ? route('client.activities.update', $editing) : route('client.activities.store') }}" class="vf-activity-form" x-data="{ activity: @js(old('activity_key', $editing?->activity_key ?? '')) }">
                @csrf
                @if($editing) @method('PUT') @else <input type="hidden" name="request_token" value="{{ old('request_token', $requestToken) }}"> @endif
                <div><label for="activity-date">Fecha de la actividad</label><input id="activity-date" type="date" name="performed_on" required min="2000-01-01" max="{{ today()->toDateString() }}" value="{{ old('performed_on', $editing?->performed_on?->toDateString() ?? $selectedDate) }}"></div>
                <div><label for="activity-type">Actividad y esfuerzo</label><select id="activity-type" name="activity_key" x-model="activity" required><option value="">Elegí una actividad</option>@foreach(collect($catalog)->groupBy('group', preserveKeys: true) as $group => $activities)<optgroup label="{{ $group }}">@foreach($activities as $key => $activity)<option value="{{ $key }}" @selected(old('activity_key', $editing?->activity_key) === $key)>{{ $activity['label'] }}</option>@endforeach</optgroup>@endforeach</select></div>
                <div x-show="activity !== 'walk_steps'"><label for="activity-minutes">Duración (minutos)</label><input id="activity-minutes" type="number" inputmode="numeric" name="duration_minutes" min="1" max="600" step="1" :required="activity !== 'walk_steps'" :disabled="activity === 'walk_steps'" value="{{ old('duration_minutes', $editing?->duration_minutes) }}" placeholder="Ej.: 30"></div>
                <div x-show="activity === 'walk_steps'" x-cloak>
                    <label for="activity-steps">Pasos adicionales caminando</label>
                    <input id="activity-steps" type="number" inputmode="numeric" name="step_count" min="1" max="60000" step="1" :required="activity === 'walk_steps'" :disabled="activity !== 'walk_steps'" value="{{ old('step_count', $editing?->step_count) }}" placeholder="Ej.: 3000" aria-describedby="activity-steps-help">
                    <p id="activity-steps-help" class="vf-activity-muted" style="margin-top:7px;">El tiempo se estima a un ritmo de referencia de 100 pasos/minuto. Si conocés el tiempo y el ritmo reales, elegí una caminata por minutos.</p>
                </div>
                <div><label for="activity-weight">Peso corporal usado (kg)</label><input id="activity-weight" type="number" inputmode="decimal" name="body_weight" min="20" max="400" step="0.01" required value="{{ old('body_weight', $editing?->body_weight ?? $suggestedWeight) }}" placeholder="Tu peso corporal" aria-describedby="activity-weight-help"><p id="activity-weight-help" class="vf-activity-muted" style="margin-top:7px;">{{ $suggestedWeight ? 'Sugerido desde tu última medición disponible. Podés corregirlo.' : 'Necesitamos tu peso para estimar el gasto. No es el peso de las pesas.' }}</p></div>
                <div class="wide" x-show="activity === 'walk_steps'" x-cloak><label class="vf-activity-check" for="activity-untracked-steps"><input id="activity-untracked-steps" type="checkbox" name="untracked_steps" value="1" :required="activity === 'walk_steps'" :disabled="activity !== 'walk_steps'" @checked(old('untracked_steps', $editing?->step_count ? 1 : 0))><span>Estos pasos no están incluidos en caminatas o corridas que ya registré. Cargo solo los pasos adicionales para evitar sumar el mismo esfuerzo dos veces.</span></label></div>
                <div class="wide"><label for="activity-notes">Notas (opcional)</label><textarea id="activity-notes" name="notes" maxlength="500">{{ old('notes', $editing?->notes) }}</textarea></div>
                <div class="wide" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;"><button type="submit" class="client-btn client-btn-primary" @disabled($isDemo)>{{ $editing ? 'Guardar cambios' : 'Guardar actividad' }}</button>@if($editing)<a href="{{ route('client.activities.index', ['date' => $selectedDate]) }}">Cancelar edición</a>@endif</div>
            </form>
        </section>

        <section class="vf-activity-panel" aria-labelledby="activity-log-heading">
            <h2 id="activity-log-heading">Actividades del {{ \Carbon\CarbonImmutable::parse($selectedDate)->format('d/m/Y') }}</h2>
            @forelse($entries as $entry)
                <article class="vf-activity-log"><div><h3>{{ $entry->activity_label }}</h3><p class="vf-activity-muted">@if($entry->step_count){{ number_format($entry->step_count, 0, ',', '.') }} pasos · @endif{{ $entry->duration_minutes }} min{{ $entry->step_count ? ' estimados' : '' }} · {{ number_format((float) $entry->body_weight, 2, ',', '.') }} kg usados en el cálculo</p>@if($entry->notes)<p class="vf-activity-muted">{{ $entry->notes }}</p>@endif<div class="vf-activity-log-actions"><a href="{{ route('client.activities.edit', $entry) }}">Editar</a><form method="POST" action="{{ route('client.activities.destroy', $entry) }}" onsubmit="return confirm('¿Eliminar esta actividad del resumen?')">@csrf @method('DELETE')<button type="submit">Eliminar</button></form></div></div><strong>≈ {{ number_format((float) $entry->active_kcal, 0, ',', '.') }} kcal</strong></article>
            @empty
                <p class="vf-activity-muted">{{ $isDemo ? 'El resumen de ejemplo representa 30 min de cardio y 60 min de fuerza. No hay registros reales.' : 'Todavía no cargaste actividades para este día.' }}</p>
            @endforelse
            @unless($isDemo)<div style="margin-top:14px;">{{ $entries->links() }}</div>@endunless
        </section>

        <section class="vf-activity-panel" aria-labelledby="activity-week-heading"><h2 id="activity-week-heading">Detalle de la semana</h2><table class="vf-activity-week"><thead><tr><th scope="col">Día</th><th scope="col">Cardio</th><th scope="col">Fuerza</th><th scope="col">Total kcal</th></tr></thead><tbody>@foreach($weekDays as $day => $totals)<tr><th scope="row">{{ \Carbon\CarbonImmutable::parse($day)->locale('es')->isoFormat('ddd DD/MM') }}</th><td>{{ number_format($totals['cardio_kcal'], 0, ',', '.') }}</td><td>{{ number_format($totals['strength_kcal'], 0, ',', '.') }}</td><td>≈ {{ number_format($totals['total_kcal'], 0, ',', '.') }}</td></tr>@endforeach</tbody></table></section>
        <details class="vf-activity-panel"><summary>Cómo se estima el gasto</summary><p class="vf-activity-muted">Usamos referencias MET del <a href="https://pacompendium.com/" target="_blank" rel="noopener noreferrer">Compendio de Actividades Físicas 2024</a>, tu peso corporal y el tiempo: calorías activas = (MET − 1) × kg × horas. El resultado es orientativo; el esfuerzo real, la técnica y las pausas cambian el gasto.</p><p class="vf-activity-muted">Para pasos, suponemos una caminata de referencia de 100 pasos por minuto y 3 MET. No conocemos tu ritmo real a partir del total de pasos.</p><p class="vf-activity-muted">Para fuerza automática, suponemos 3 segundos por repetición y el descanso de la rutina, con un máximo de 5 minutos entre series y 90 segundos si falta el dato. No agregamos descanso después de la última serie del día de rutina. Redondeamos el tiempo hacia arriba y usamos 3,5 MET. El tiempo es modelado, no medido; el peso levantado no se convierte directamente en calorías. El peso corporal es el último registrado hasta esa fecha. Cargar tiempo de fuerza manual reemplaza este modelo para ese día. Si corregís series o mediciones, cambia la estimación automática; las actividades guardadas a mano conservan sus datos originales.</p></details>
    </div>
</x-layouts.client>
