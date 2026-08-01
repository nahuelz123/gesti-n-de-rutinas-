<?php

namespace App\Filament\Resources\FoodItems\Pages;

use App\Filament\Resources\FoodItems\FoodItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFoodItems extends ListRecords
{
    protected static string $resource = FoodItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
