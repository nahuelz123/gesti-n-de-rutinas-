<?php

namespace App\Filament\Resources\FoodItems\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FoodItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge(),

                TextColumn::make('calories_per_100g')
                    ->label('Kcal /100g')
                    ->sortable(),

                TextColumn::make('protein_per_100g')
                    ->label('Prot /100g')
                    ->suffix('g'),

                TextColumn::make('carbs_per_100g')
                    ->label('Carb /100g')
                    ->suffix('g'),

                TextColumn::make('fat_per_100g')
                    ->label('Gras /100g')
                    ->suffix('g'),

                IconColumn::make('is_global')
                    ->label('Global')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'proteína' => 'Proteína',
                        'carbohidrato' => 'Carbohidrato',
                        'legumbre' => 'Legumbre',
                        'verdura' => 'Verdura',
                        'fruta' => 'Fruta',
                        'lácteo' => 'Lácteo',
                        'grasa' => 'Grasa',
                        'otro' => 'Otro',
                    ]),

                TernaryFilter::make('is_global')
                    ->label('Global'),
            ])
            ->defaultSort('name');
    }
}
