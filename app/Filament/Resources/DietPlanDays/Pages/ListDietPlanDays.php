<?php

namespace App\Filament\Resources\DietPlanDays\Pages;

use App\Filament\Resources\DietPlanDays\DietPlanDayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDietPlanDays extends ListRecords
{
    protected static string $resource = DietPlanDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
