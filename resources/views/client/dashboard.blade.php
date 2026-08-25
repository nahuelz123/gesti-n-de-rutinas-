<x-layouts.client>

<div class="rw" style="padding: var(--space-5) var(--space-4);">

    {{-- 1. HERO PRINCIPAL --}}
    <div style="margin-bottom: var(--space-6);">
        <h1 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; color: var(--clr-text); margin-bottom: 2px;">
            Hola, {{ explode(' ', auth()->user()->name)[0] }} 👋
        </h1>
        <p style="font-size: 14px; color: var(--clr-text-muted); font-weight: 500;">
            Esto es lo que tenés para hoy
        </p>
    </div>

    {{-- 2. CARD PRINCIPAL — ENTRENAMIENTO --}}
    <h2 style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--clr-text-muted); margin-bottom: var(--space-3);">Entrenamiento</h2>
    
    @if ($active)
        <x-client.card style="border-color: rgba(230,57,70,0.3); background: linear-gradient(180deg, rgba(230,57,70,0.05) 0%, transparent 100%);">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: var(--space-4);">
                <div>
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--clr-text); margin-bottom: 4px;">{{ $active->routine->title }}</h3>
                    <p style="font-size: 14px; color: var(--clr-text-muted);">{{ $active->routine->days->count() }} días de entrenamiento</p>
                </div>
                <div style="width: 40px; height: 40px; background: var(--clr-primary-alpha); color: var(--clr-primary); border-radius: var(--radius-pill); display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px; height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                </div>
            </div>
            
            <a href="{{ route('client.routines.active') }}" style="text-decoration:none;">
                <x-client.action-button variant="primary">
                    COMENZAR ENTRENAMIENTO
                </x-client.action-button>
            </a>
        </x-client.card>
    @else
        <x-client.empty-state 
            title="Sin entrenamiento" 
            description="No tenés una rutina asignada para hoy. Cuando tu Coach te asigne una, la verás acá."
            icon="💪"
            style="margin-bottom: var(--space-6);"
        >
            <x-slot:action>
                <a href="{{ route('client.chat.index') }}" style="text-decoration:none;">
                    <x-client.action-button variant="secondary" style="min-height:44px; font-size:14px;">Hablar con mi Coach</x-client.action-button>
                </a>
            </x-slot:action>
        </x-client.empty-state>
    @endif

    {{-- 3. CARD DE NUTRICIÓN --}}
    <h2 style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--clr-text-muted); margin-top: var(--space-6); margin-bottom: var(--space-3);">Nutrición de hoy</h2>
    
    @if ($dietAssignment && $todayDietDay)
        @php
            $caloriesTarget = $nutritionSummary['target']['calories'] ?? 0;
            $proteinTarget = $nutritionSummary['target']['protein'] ?? 0;
            $carbsTarget = $nutritionSummary['target']['carbs'] ?? 0;
            $fatTarget = $nutritionSummary['target']['fat'] ?? 0;
            
            $caloriesEaten = $nutritionSummary['eaten']['calories'] ?? 0;
            $nutriPct = $caloriesTarget > 0 ? min(100, round($caloriesEaten / $caloriesTarget * 100)) : 0;
        @endphp
        
        <x-client.card>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--space-5);">
                <div>
                    <div style="font-size: 32px; font-weight: 800; letter-spacing: -0.03em; color: var(--clr-text); line-height: 1;">
                        {{ round($caloriesTarget) }}
                    </div>
                    <div style="font-size: 13px; color: var(--clr-text-muted); font-weight: 500; margin-top: 4px;">kcal objetivo diario</div>
                </div>
                @if($nutritionSummary['meals_total'] > 0)
                    <div style="text-align: right;">
                        <div style="font-size: 16px; font-weight: 700; color: var(--clr-success);">{{ $nutritionSummary['meals_done'] }}/{{ $nutritionSummary['meals_total'] }}</div>
                        <div style="font-size: 12px; color: var(--clr-text-muted);">comidas hoy</div>
                    </div>
                @endif
            </div>

            <div style="display: flex; gap: var(--space-2); margin-bottom: var(--space-5);">
                <div style="flex:1; background: var(--clr-surface-elevated); padding: var(--space-3); border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 11px; color: var(--clr-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Proteína</div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--clr-text);">{{ round($proteinTarget) }}g</div>
                </div>
                <div style="flex:1; background: var(--clr-surface-elevated); padding: var(--space-3); border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 11px; color: var(--clr-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Carbos</div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--clr-text);">{{ round($carbsTarget) }}g</div>
                </div>
                <div style="flex:1; background: var(--clr-surface-elevated); padding: var(--space-3); border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 11px; color: var(--clr-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Grasas</div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--clr-text);">{{ round($fatTarget) }}g</div>
                </div>
            </div>

            <a href="{{ route('client.nutrition.index') }}" style="text-decoration:none;">
                <x-client.action-button variant="secondary">
                    VER MI DIETA
                </x-client.action-button>
            </a>
        </x-client.card>
    @else
        <x-client.empty-state 
            title="Sin plan nutricional" 
            description="Hoy no tenés comidas planificadas en tu dieta."
            icon="🍎"
            style="margin-bottom: var(--space-6);"
        >
             <x-slot:action>
                 <a href="{{ route('client.nutrition.index') }}" style="text-decoration:none;">
                     <x-client.action-button variant="secondary" style="min-height:44px; font-size:14px;">Ver dieta completa</x-client.action-button>
                 </a>
             </x-slot:action>
        </x-client.empty-state>
    @endif

    {{-- 4. ACCESOS RÁPIDOS --}}
    <h2 style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--clr-text-muted); margin-top: var(--space-6); margin-bottom: var(--space-3);">Accesos rápidos</h2>
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-3);">
        <a href="{{ route('client.progress.index') }}" style="background: var(--clr-surface); border: 1px solid var(--clr-border); border-radius: var(--radius-md); padding: var(--space-4); text-decoration: none; display: flex; flex-direction: column; gap: var(--space-2);">
            <div style="width: 32px; height: 32px; background: rgba(96,165,250,0.12); color: #60a5fa; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px; height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
            </div>
            <span style="font-size: 14px; font-weight: 600; color: var(--clr-text);">Progreso</span>
        </a>

        <a href="{{ route('client.recipes.index') }}" style="background: var(--clr-surface); border: 1px solid var(--clr-border); border-radius: var(--radius-md); padding: var(--space-4); text-decoration: none; display: flex; flex-direction: column; gap: var(--space-2);">
            <div style="width: 32px; height: 32px; background: var(--clr-success-alpha); color: var(--clr-success); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px; height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            </div>
            <span style="font-size: 14px; font-weight: 600; color: var(--clr-text);">Recetas</span>
        </a>

        <a href="{{ route('client.chat.index') }}" style="background: var(--clr-surface); border: 1px solid var(--clr-border); border-radius: var(--radius-md); padding: var(--space-4); text-decoration: none; display: flex; flex-direction: column; gap: var(--space-2);">
            <div style="width: 32px; height: 32px; background: rgba(230,57,70,0.12); color: #e63946; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px; height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-.72 0-1.437-.023-2.148-.069m8.148-6.573V6.75c0-1.108-.806-2.057-1.907-2.185a48.507 48.507 0 0 0-11.186 0c-1.1.128-1.907 1.077-1.907 2.185v4.286c0 1.108.806 2.057 1.907 2.185.106.012.213.023.32.033m9.166-4.5H3.75"/></svg>
            </div>
            <span style="font-size: 14px; font-weight: 600; color: var(--clr-text);">Coach</span>
        </a>

        <a href="{{ route('client.ai-chat.index') }}" style="background: var(--clr-surface); border: 1px solid var(--clr-border); border-radius: var(--radius-md); padding: var(--space-4); text-decoration: none; display: flex; flex-direction: column; gap: var(--space-2);">
            <div style="width: 32px; height: 32px; background: rgba(192,132,252,0.12); color: #c084fc; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px; height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
            </div>
            <span style="font-size: 14px; font-weight: 600; color: var(--clr-text);">Asistente IA</span>
        </a>
    </div>

</div>

</x-layouts.client>
