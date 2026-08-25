<x-layouts.client>
    @if($assignment)
        <livewire:client.workout-logger :assignment="$assignment" />
    @else
        <div style="padding: var(--space-5) var(--space-4);">
            <x-client.empty-state 
                title="Sin rutina activa" 
                description="No tenés ninguna rutina asignada actualmente."
                icon="💪"
            >
                <x-slot:action>
                    <a href="{{ route('client.dashboard') }}" style="text-decoration:none;">
                        <x-client.action-button variant="secondary">Volver al inicio</x-client.action-button>
                    </a>
                </x-slot:action>
            </x-client.empty-state>
        </div>
    @endif
</x-layouts.client>
