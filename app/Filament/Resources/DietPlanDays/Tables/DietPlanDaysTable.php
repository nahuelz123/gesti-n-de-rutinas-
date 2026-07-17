<?php

namespace App\Filament\Resources\DietPlanDays\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DietPlanDaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dietPlan.title')
                    ->label('Plan de dieta')
                    ->searchable(),

                TextColumn::make('day_of_week')
                    ->label('Día')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miércoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo',
                        default => $state,
                    })
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
