<?php

namespace App\Filament\Resources\DietPlanDays\Pages;

use App\Filament\Resources\DietPlanDays\DietPlanDayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDietPlanDay extends EditRecord
{
    protected static string $resource = DietPlanDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
