<?php

namespace App\Filament\Resources\Routines\Pages;

use App\Filament\Resources\Routines\RoutineResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateRoutine extends CreateRecord
{
    protected static string $resource = RoutineResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($draft = session()->pull('routine-photo-draft')) {
            $unmatched = $draft['unmatched'];
            unset($draft['unmatched']);

            // Los repeaters de relaciones recargan la BD durante fill() y borran el
            // borrador. Cargamos su estado después de inicializar el formulario.
            $days = [];
            foreach ($draft['days'] as $day) {
                $exercises = [];
                foreach ($day['exercises'] as $exercise) {
                    $exercises[(string) Str::uuid()] = $exercise;
                }
                $day['exercises'] = $exercises;
                $days[(string) Str::uuid()] = $day;
            }
            $draft['days'] = $days;

            $this->form->rawState($draft + ['gym_id' => Auth::user()?->gym_id, 'coach_id' => Auth::id()]);

            Notification::make()->title('Borrador listo: revisá todos los datos antes de crear la rutina')
                ->body($unmatched ? 'Completá los campos sin resolver: '.implode(', ', $unmatched) : 'Revisá ejercicios, series y repeticiones antes de guardar.')
                ->warning()->persistent()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('leerFoto')
                ->label('Importar foto, PDF, Word o Excel')
                ->url(route('routines.photo.show')),
        ];
    }
}
