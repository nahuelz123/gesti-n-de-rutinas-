<?php

namespace App\Filament\Resources\DietAssignments\Pages;

use App\Filament\Resources\DietAssignments\DietAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDietAssignment extends EditRecord
{
    protected static string $resource = DietAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
