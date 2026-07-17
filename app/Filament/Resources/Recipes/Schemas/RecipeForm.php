<?php

namespace App\Filament\Resources\Recipes\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RecipeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('created_by_id')
                ->default(fn () => Auth::id()),

            Section::make('Datos generales')
                ->columns(2)
                ->components([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Descripción')
                        ->rows(3)
                        ->columnSpanFull()
                        ->nullable(),

                    Select::make('meal_type')
                        ->label('Tipo de comida')
                        ->native(false)
                        ->options([
                            'desayuno' => 'Desayuno',
                            'almuerzo' => 'Almuerzo',
                            'merienda' => 'Merienda',
                            'cena' => 'Cena',
                            'pre_entrenamiento' => 'Pre-entrenamiento',
                            'post_entrenamiento' => 'Post-entrenamiento',
                        ])
                        ->helperText('Vacío = sirve para cualquier comida')
                        ->nullable(),

                    Toggle::make('is_global')
                        ->label('Receta global (visible para todos los gimnasios)')
                        ->default(true)
                        ->visible(fn () => Auth::user()?->role === 'super_admin'),

                    TextInput::make('photo_url')
                        ->label('URL de foto')
                        ->url()
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('video_url')
                        ->label('URL de video')
                        ->url()
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('prep_time')
                        ->label('Tiempo de preparación (min)')
                        ->numeric()
                        ->nullable(),

                    TextInput::make('servings')
                        ->label('Porciones')
                        ->numeric()
                        ->default(1)
                        ->required(),
                ]),

            Section::make('Macros por porción')
                ->columns(4)
                ->components([
                    TextInput::make('calories')
                        ->label('Calorías')
                        ->numeric()
                        ->suffix('kcal')
                        ->nullable(),

                    TextInput::make('protein')
                        ->label('Proteína')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('g')
                        ->nullable(),

                    TextInput::make('carbs')
                        ->label('Carbohidratos')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('g')
                        ->nullable(),

                    TextInput::make('fat')
                        ->label('Grasas')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('g')
                        ->nullable(),
                ]),

            Section::make('Ingredientes')
                ->components([
                    Repeater::make('ingredients')
                        ->relationship('ingredients')
                        ->label('')
                        ->columns(3)
                        ->schema([
                            TextInput::make('name')
                                ->label('Ingrediente')
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->step(0.01)
                                ->nullable(),

                            TextInput::make('unit')
                                ->label('Unidad')
                                ->placeholder('gr, ml, taza, unidad...')
                                ->nullable(),
                        ])
                        ->reorderable()
                        ->orderColumn('order')
                        ->defaultItems(1)
                        ->addActionLabel('Agregar ingrediente')
                        ->collapsible(),
                ]),

            Section::make('Preparación')
                ->components([
                    Repeater::make('instructions')
                        ->relationship('instructions')
                        ->label('')
                        ->schema([
                            Textarea::make('instruction')
                                ->label('Paso')
                                ->required()
                                ->rows(2),
                        ])
                        ->reorderable()
                        ->orderColumn('step')
                        ->defaultItems(1)
                        ->addActionLabel('Agregar paso')
                        ->collapsible(),
                ]),
        ]);
    }
}
