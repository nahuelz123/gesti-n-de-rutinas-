<?php

namespace App\Filament\Resources\FoodItems\Pages;

use App\Filament\Resources\FoodItems\FoodItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFoodItem extends EditRecord
{
    protected static string $resource = FoodItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
