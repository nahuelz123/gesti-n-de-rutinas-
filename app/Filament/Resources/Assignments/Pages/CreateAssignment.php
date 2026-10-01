<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Models\Assignment;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($clientId = request()->query('client_id')) {
            $this->form->fill(['client_id' => (int) $clientId]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? null) === 'active') {
            $data['end_date'] = null;
        }

        // Si se marca como completed y no tiene fecha de fin, la seteamos
        if (($data['status'] ?? null) === 'completed' && empty($data['end_date'])) {
            $data['end_date'] = now()->toDateString();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            // Serializa asignaciones simultáneas para un mismo alumno.
            User::query()->whereKey($data['client_id'])->lockForUpdate()->firstOrFail();

            // Validar y crear antes de cerrar la anterior. Si falla cualquier paso,
            // la transacción conserva intacta la asignación que ya estaba activa.
            $assignment = parent::handleRecordCreation($data);

            if ($assignment->status === 'active') {
                Assignment::query()
                    ->where('gym_id', $assignment->gym_id)
                    ->where('client_id', $assignment->client_id)
                    ->where('id', '!=', $assignment->id)
                    ->whereNull('end_date')
                    ->where('status', 'active')
                    ->update([
                        'status' => 'completed',
                        'end_date' => now()->toDateString(),
                    ]);
            }

            return $assignment;
        });
    }
}
