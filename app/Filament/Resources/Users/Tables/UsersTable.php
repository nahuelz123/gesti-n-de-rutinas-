<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),

            TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->sortable(),

            TextColumn::make('role')
                ->label('Rol')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'super_admin' => 'Super Admin',
                    'admin' => 'Administrador',
                    'coach' => 'Coach',
                    'client' => 'Cliente',
                    default => $state,
                })
                ->colors([
                    'primary' => 'admin',
                    'warning' => 'coach',
                    'success' => 'client',
                ])
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Creado')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            \Filament\Tables\Filters\SelectFilter::make('role')
                ->label('Rol')
                ->options([
                    'admin' => 'Administrador',
                    'coach' => 'Coach',
                    'client' => 'Cliente',
                ]),
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
