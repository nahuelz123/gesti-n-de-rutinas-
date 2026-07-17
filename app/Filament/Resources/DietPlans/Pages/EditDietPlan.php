<?php

namespace App\Filament\Resources\DietPlans\Pages;

use App\Filament\Resources\DietPlans\DietPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDietPlan extends EditRecord
{
    protected static string $resource = DietPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
