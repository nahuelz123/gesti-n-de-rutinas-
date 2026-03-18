<?php

namespace App\Filament\Resources\Routines\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DaysRelationManager extends RelationManager
{
    protected static string $relationship = 'days';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('day_number')
                ->label('Día')
                ->numeric()
                ->required()
                ->minValue(1),

            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('title')
        ->columns([
            TextColumn::make('day_number')
                ->label('Día')
                ->sortable(),

            TextColumn::make('title')
                ->label('Título')
                ->searchable(),
        ])
        ->headerActions([
            CreateAction::make(),
        ])
        ->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->defaultSort('day_number')
        ->recordUrl(fn ($record) => \App\Filament\Resources\RoutineDays\RoutineDayResource::getUrl('edit', ['record' => $record]));
}

}
