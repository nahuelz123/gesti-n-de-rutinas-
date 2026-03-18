<?php

namespace App\Filament\Resources\RoutineDays\Pages;

use App\Filament\Resources\RoutineDays\RoutineDayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoutineDays extends ListRecords
{
    protected static string $resource = RoutineDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
