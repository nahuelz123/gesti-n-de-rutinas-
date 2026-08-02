<?php

namespace App\Filament\Resources\FoodItems\Pages;

use App\Filament\Resources\FoodItems\FoodItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFoodItem extends CreateRecord
{
    protected static string $resource = FoodItemResource::class;
}
