<?php

namespace App\Filament\Resources\RoutineDays\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoutineDayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('routine_id')
                    ->relationship('routine', 'title')
                    ->required(),
                TextInput::make('day_number')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
            ]);
    }
}
