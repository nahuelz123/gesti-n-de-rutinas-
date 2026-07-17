<?php

namespace App\Filament\Resources\DietPlans\Pages;

use App\Filament\Resources\DietPlans\DietPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDietPlans extends ListRecords
{
    protected static string $resource = DietPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
