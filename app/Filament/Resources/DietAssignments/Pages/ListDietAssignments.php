<?php

namespace App\Filament\Resources\DietAssignments\Pages;

use App\Filament\Resources\DietAssignments\DietAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDietAssignments extends ListRecords
{
    protected static string $resource = DietAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
