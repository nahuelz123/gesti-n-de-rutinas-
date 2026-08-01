<?php

namespace App\Filament\Resources\FoodItems\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class FoodItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('created_by_id')
                ->default(fn () => Auth::id()),

            Section::make('Alimento')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('category')
                        ->label('Categoría')
                        ->native(false)
                        ->options([
                            'proteína' => 'Proteína',
                            'carbohidrato' => 'Carbohidrato',
                            'legumbre' => 'Legumbre',
                            'verdura' => 'Verdura',
                            'fruta' => 'Fruta',
                            'lácteo' => 'Lácteo',
                            'grasa' => 'Grasa',
                            'otro' => 'Otro',
                        ])
                        ->nullable(),

                    Toggle::make('is_global')
                        ->label('Alimento global (visible para todos los gimnasios)')
                        ->default(false)
                        ->visible(fn () => Auth::user()?->role === 'super_admin'),
                ]),

            Section::make('Macros cada 100g')
                ->columns(4)
                ->description('El alumno va a cargar cualquier cantidad en gramos y la app calcula todo en base a esto.')
                ->components([
                    TextInput::make('calories_per_100g')
                        ->label('Calorías')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('kcal')
                        ->required(),

                    TextInput::make('protein_per_100g')
                        ->label('Proteína')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('g')
                        ->default(0)
                        ->required(),

                    TextInput::make('carbs_per_100g')
                        ->label('Carbohidratos')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('g')
                        ->default(0)
                        ->required(),

                    TextInput::make('fat_per_100g')
                        ->label('Grasas')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('g')
                        ->default(0)
                        ->required(),
                ]),
        ]);
    }
}
