<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Services\AssignmentTransition;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

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

    protected function handleRecordCreation(array $data): Model
    {
        return app(AssignmentTransition::class)->save(
            $data,
            fn (array $normalized): Model => parent::handleRecordCreation($normalized),
        );
    }
}
