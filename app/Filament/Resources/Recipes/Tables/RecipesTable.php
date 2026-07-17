<?php

namespace App\Filament\Resources\Recipes\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RecipesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('meal_type')
                    ->label('Tipo de comida')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'desayuno' => 'Desayuno',
                        'almuerzo' => 'Almuerzo',
                        'merienda' => 'Merienda',
                        'cena' => 'Cena',
                        'pre_entrenamiento' => 'Pre-entrenamiento',
                        'post_entrenamiento' => 'Post-entrenamiento',
                        default => 'Cualquiera',
                    }),

                TextColumn::make('calories')
                    ->label('Calorías')
                    ->suffix(' kcal')
                    ->sortable(),

                IconColumn::make('is_global')
                    ->label('Global')
                    ->boolean(),

                TextColumn::make('creator.name')
                    ->label('Creada por')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('meal_type')
                    ->label('Tipo de comida')
                    ->options([
                        'desayuno' => 'Desayuno',
                        'almuerzo' => 'Almuerzo',
                        'merienda' => 'Merienda',
                        'cena' => 'Cena',
                        'pre_entrenamiento' => 'Pre-entrenamiento',
                        'post_entrenamiento' => 'Post-entrenamiento',
                    ]),

                TernaryFilter::make('is_global')
                    ->label('Global'),
            ]);
    }
}
