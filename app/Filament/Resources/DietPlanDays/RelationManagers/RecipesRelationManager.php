<?php

namespace App\Filament\Resources\DietPlanDays\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecipesRelationManager extends RelationManager
{
    protected static string $relationship = 'recipes';

    protected static ?string $title = 'Comidas del día';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('recipe_id')
                ->label('Receta')
                ->relationship(
                    'recipe',
                    'title',
                    fn (Builder $query) => $query->where(function (Builder $q) {
                        $q->where('is_global', true)
                            ->orWhereHas('creator', fn (Builder $cq) => $cq->where('gym_id', Auth::user()?->gym_id));
                    })
                )
                ->searchable()
                ->preload()
                ->required(),

            Select::make('meal_type')
                ->label('Tipo de comida')
                ->native(false)
                ->helperText('Si agregás más de una receta con el mismo tipo (ej: 2 opciones de "Almuerzo"), el cliente va a poder elegir cuál de las dos hizo.')
                ->options([
                    'desayuno' => 'Desayuno',
                    'almuerzo' => 'Almuerzo',
                    'merienda' => 'Merienda',
                    'cena' => 'Cena',
                    'pre_entrenamiento' => 'Pre-entrenamiento',
                    'post_entrenamiento' => 'Post-entrenamiento',
                ])
                ->required(),

            TextInput::make('servings')
                ->label('Porciones')
                ->numeric()
                ->step(0.1)
                ->default(1)
                ->required(),

            TextInput::make('order')
                ->label('Orden')
                ->numeric()
                ->default(0)
                ->required(),

            Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull()
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order')
            ->columns([
                TextColumn::make('meal_type')
                    ->label('Comida')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'desayuno' => 'Desayuno',
                        'almuerzo' => 'Almuerzo',
                        'merienda' => 'Merienda',
                        'cena' => 'Cena',
                        'pre_entrenamiento' => 'Pre-entrenamiento',
                        'post_entrenamiento' => 'Post-entrenamiento',
                        default => $state,
                    })
                    ->badge(),

                TextColumn::make('recipe.title')
                    ->label('Receta')
                    ->searchable(),

                TextColumn::make('servings')
                    ->label('Porciones'),

                TextColumn::make('recipe.calories')
                    ->label('Calorías')
                    ->suffix(' kcal'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('order');
    }
}
