<?php

namespace App\Filament\Resources\DietPlans\Schemas;

use App\Models\Recipe;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
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

            Section::make('Información del Plan')
                ->schema([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255),

                    Grid::make(2)
                        ->schema([
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
                        ]),

                    Textarea::make('description')
                        ->label('Descripción')
                        ->columnSpanFull()
                        ->rows(3)
                        ->nullable(),
                ]),

            Section::make('Constructor Visual')
                ->schema([
                    Repeater::make('days')
                        ->relationship('days')
                        ->label('Días de Dieta')
                        ->addActionLabel('+ Agregar día')
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => $state['day_of_week'] ?? 'Día sin especificar')
                        ->schema([
                            Select::make('day_of_week')
                                ->label('Día')
                                ->native(false)
                                ->options([
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miercoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sabado' => 'Sábado',
                                    'domingo' => 'Domingo',
                                ])
                                ->distinct()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                                
                            Textarea::make('notes')
                                ->label('Notas del día')
                                ->rows(2)
                                ->nullable(),

                            Repeater::make('recipes')
                                ->relationship('recipes')
                                ->label('Recetas')
                                ->addActionLabel('+ Agregar receta')
                                ->collapsible()
                                ->cloneable()
                                ->orderColumn('order')
                                ->itemLabel(fn (array $state): ?string => Recipe::find($state['recipe_id'] ?? null)?->title ?? 'Nueva receta')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
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
                                                ->required(),
                                                
                                            Select::make('recipe_id')
                                                ->label('Seleccionar Receta')
                                                ->relationship(
                                                    name: 'recipe',
                                                    titleAttribute: 'title',
                                                    modifyQueryUsing: fn (Builder $query) => $query->where(function ($q) {
                                                        $user = Auth::user();
                                                        $q->where('is_global', true)
                                                          ->orWhere('gym_id', $user?->gym_id);
                                                    })
                                                )
                                                ->getOptionLabelFromRecordUsing(fn (Recipe $record) => ($record->is_global ? '🌐 ' : '🏠 ') . $record->title . ($record->is_global ? ' (Catálogo)' : ' (Mi gym)'))
                                                ->searchable()
                                                ->preload()
                                                ->required(),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('servings')
                                                ->label('Porciones')
                                                ->numeric()
                                                ->step(0.1)
                                                ->default(1)
                                                ->required(),
                                                
                                            Textarea::make('notes')
                                                ->label('Notas (Opcional)')
                                                ->rows(2)
                                                ->nullable(),
                                        ])
                                ])
                        ])
                ])
        ]);
    }
}
