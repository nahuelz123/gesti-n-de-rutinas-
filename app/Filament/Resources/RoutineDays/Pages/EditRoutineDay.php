<?php

namespace App\Filament\Resources\RoutineDays\Pages;

use App\Filament\Resources\RoutineDays\RoutineDayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoutineDay extends EditRecord
{
    protected static string $resource = RoutineDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
