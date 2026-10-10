<?php

namespace App\Filament\Resources\Exercises\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Support\ExerciseNames;

class ExercisesTable
{
    public static function configure(Table $table): Table
{
    return $table
        ->splitSearchTerms(false)
        ->searchPlaceholder('Nombre habitual o del catálogo')
        ->columns([
            ImageColumn::make('gif_url')
                ->label('')
                ->size(48)
                ->circular(false)
                ->square()
                ->extraImgAttributes(['loading' => 'lazy']),

            TextColumn::make('title')
                ->label('Título')
                ->searchable(query: fn (Builder $query, string $search): Builder => $query->searchByName($search))
                ->sortable()
                ->formatStateUsing(fn ($state, $record) => $record->is_global 
                    ? '🌐 ' . ExerciseNames::displayName($state) . ' (Catálogo)'
                    : '🏠 ' . ExerciseNames::displayName($state) . ' (Mi gym)'
                ),

            TextColumn::make('muscle_group')
                ->label('Músculo')
                ->badge()
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Creado')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            \Filament\Tables\Filters\SelectFilter::make('muscle_group')
                ->label('Músculo')
                ->options([
                    'pecho' => 'Pecho',
                    'espalda' => 'Espalda',
                    'piernas' => 'Piernas',
                    'gluteos' => 'Glúteos',
                    'hombros' => 'Hombros',
                    'biceps' => 'Bíceps',
                    'triceps' => 'Tríceps',
                    'abdomen' => 'Abdomen',
                    'cardio' => 'Cardio',
                    'fullbody' => 'Full Body',
                ]),

            \Filament\Tables\Filters\TernaryFilter::make('is_global')
                ->label('Global'),
        ])
        ->recordActions([
            EditAction::make(),
        ])
        ->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
}

}
