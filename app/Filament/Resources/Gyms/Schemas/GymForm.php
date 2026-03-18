<?php

namespace App\Filament\Resources\Gyms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GymForm
{
  public static function configure(Schema $schema): Schema
{
    return $schema
        ->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('plan')
                ->required()
                ->default('basic')
                ->maxLength(50),

            Toggle::make('active')
                ->default(true),

            TextInput::make('logo')
                ->nullable()
                ->maxLength(255),
        ]);
}

}
