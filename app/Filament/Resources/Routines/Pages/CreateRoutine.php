<?php

namespace App\Filament\Resources\Routines\Pages;

use App\Filament\Resources\Routines\RoutineResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateRoutine extends CreateRecord
{
    protected static string $resource = RoutineResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($draft = session()->pull('routine-photo-draft')) {
            $unmatched = $draft['unmatched'];
            unset($draft['unmatched']);

            $this->form->fill($draft + ['gym_id' => Auth::user()?->gym_id, 'coach_id' => Auth::id()]);

            Notification::make()->title('Borrador listo: revisá todos los datos antes de crear la rutina')
                ->body($unmatched ? 'Completá los campos sin resolver: '.implode(', ', $unmatched) : 'Revisá ejercicios, series y repeticiones antes de guardar.')
                ->warning()->persistent()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('leerFoto')
                ->label('Cargar rutina desde foto')
                ->url(route('routines.photo.show')),
        ];
    }
}
