<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Models\Assignment;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si se crea una asignación ACTIVA,
        // cerramos cualquier asignación activa anterior del cliente
        if (($data['status'] ?? null) === 'active') {
            Assignment::query()
                ->where('gym_id', $data['gym_id'])
                ->where('client_id', $data['client_id'])
                ->whereNull('end_date')
                ->where('status', 'active')
                ->update([
                    'status' => 'completed',
                    'end_date' => now()->toDateString(),
                ]);

            // La nueva queda activa
            $data['end_date'] = null;
        }

        // Si se marca como completed y no tiene fecha de fin, la seteamos
        if (($data['status'] ?? null) === 'completed' && empty($data['end_date'])) {
            $data['end_date'] = now()->toDateString();
        }

        return $data;
    }
}
