<?php

namespace App\Filament\Resources\DietAssignments\Pages;

use App\Filament\Resources\DietAssignments\DietAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDietAssignment extends CreateRecord
{
    protected static string $resource = DietAssignmentResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($clientId = request()->query('client_id')) {
            $this->form->fill(['client_id' => (int) $clientId]);
        }
    }
}
