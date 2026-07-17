<?php

namespace App\Filament\Resources\DietPlans\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DietPlanForm
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

            Select::make('goal')
                ->label('Objetivo')
                ->native(false)
                ->options([
                    'perdida_peso' => 'Pérdida de peso',
                    'ganancia_muscular' => 'Ganancia muscular',
                    'mantenimiento' => 'Mantenimiento',
                    'rendimiento' => 'Rendimiento',
                ])
                ->nullable(),

            TextInput::make('target_calories')
                ->label('Calorías diarias objetivo')
                ->numeric()
                ->suffix('kcal')
                ->nullable(),

            Textarea::make('description')
                ->label('Descripción')
                ->columnSpanFull()
                ->rows(3)
                ->nullable(),
        ]);
    }
}
