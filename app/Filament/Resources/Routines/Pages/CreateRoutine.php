<?php

namespace App\Filament\Resources\Routines\Pages;

use App\Filament\Resources\Routines\RoutineResource;
use App\Services\RoutinePhotoReader;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateRoutine extends CreateRecord
{
    protected static string $resource = RoutineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('leerFoto')
                ->label('Cargar rutina desde foto')
                ->form([
                    FileUpload::make('photo')->label('Foto de la rutina')
                        ->disk('local')->directory('routine-imports')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(8192)->required(),
                ])
                ->action(function (array $data, RoutinePhotoReader $reader): void {
                    $path = $data['photo'];
                    try {
                        $disk = Storage::disk('local');
                        $mime = $disk->mimeType($path);
                        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                            throw new \RuntimeException('El archivo debe ser JPG, PNG o WebP.');
                        }
                        $draft = $reader->read($disk->get($path), $mime, Auth::user()?->gym_id);
                        $unmatched = $draft['unmatched'];
                        unset($draft['unmatched']);
                        $this->form->fill($draft + ['gym_id' => Auth::user()?->gym_id, 'coach_id' => Auth::id()]);
                        Notification::make()->title('Borrador listo: revisá todos los datos y confirmá con Crear')
                            ->body($unmatched ? 'Completá los campos sin resolver: '.implode(', ', $unmatched) : 'Revisá los ejercicios, series y repeticiones antes de guardar.')
                            ->warning()->persistent()->send();
                    } catch (Throwable $error) {
                        report($error);
                        Notification::make()->title('No se pudo leer la rutina')->body($error->getMessage())->danger()->persistent()->send();
                    } finally {
                        Storage::disk('local')->delete($path);
                    }
                }),
        ];
    }
}
