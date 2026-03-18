<?php

namespace App\Filament\Resources\Routines\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RoutineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('gym_id')
                ->default(fn () => Auth::user()?->gym_id)
                ->dehydrated(),

            Hidden::make('coach_id')
                ->default(fn () => Auth::id())
                ->dehydrated(),

            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->label('Descripción')
                ->columnSpanFull()
                ->nullable(),
        ]);
    }
}
