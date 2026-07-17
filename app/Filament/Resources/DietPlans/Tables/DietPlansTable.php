<?php

namespace App\Filament\Resources\DietPlans\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DietPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gym.name')
                    ->label('Gimnasio')
                    ->searchable(),

                TextColumn::make('coach.name')
                    ->label('Coach')
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),

                TextColumn::make('goal')
                    ->label('Objetivo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'perdida_peso' => 'Pérdida de peso',
                        'ganancia_muscular' => 'Ganancia muscular',
                        'mantenimiento' => 'Mantenimiento',
                        'rendimiento' => 'Rendimiento',
                        default => '-',
                    }),

                TextColumn::make('target_calories')
                    ->label('Calorías objetivo')
                    ->suffix(' kcal'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
