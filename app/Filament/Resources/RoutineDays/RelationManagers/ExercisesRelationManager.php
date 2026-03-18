<?php

namespace App\Filament\Resources\RoutineDays\RelationManagers;

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
use Illuminate\Support\Facades\Auth;

class ExercisesRelationManager extends RelationManager
{
    protected static string $relationship = 'exercises';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('exercise_id')
                ->relationship(
                    'exercise',
                    'title',
                    fn($query) => $query->where(function ($q) {
                        $q->whereNull('gym_id')
                            ->orWhere('gym_id', Auth::user()?->gym_id);
                    })
                )
                ->searchable()
                ->preload()
                ->required(),


            TextInput::make('sets')
                ->label('Series')
                ->numeric()
                ->required()
                ->minValue(1),

            TextInput::make('reps')
                ->label('Reps')
                ->required()
                ->maxLength(20)
                ->helperText('Ej: 8-10, 10, 12-15'),

            TextInput::make('rest')
                ->label('Descanso')
                ->maxLength(20)
                ->nullable()
                ->helperText('Ej: 60s, 90s, 2-3min'),

            Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull()
                ->nullable(),

            TextInput::make('order')
                ->label('Orden')
                ->numeric()
                ->default(0)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('exercise.title')
                    ->label('Ejercicio')
                    ->searchable(),

                TextColumn::make('sets')
                    ->label('Series'),

                TextColumn::make('reps')
                    ->label('Reps'),

                TextColumn::make('rest')
                    ->label('Descanso'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('order');
    }
}
