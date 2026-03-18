<?php

namespace App\Filament\Resources\Routines;

use App\Filament\Resources\Routines\Pages\CreateRoutine;
use App\Filament\Resources\Routines\Pages\EditRoutine;
use App\Filament\Resources\Routines\Pages\ListRoutines;
use App\Filament\Resources\Routines\Schemas\RoutineForm;
use App\Filament\Resources\Routines\Tables\RoutinesTable;
use App\Models\Routine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RoutineResource extends Resource
{
    protected static ?string $model = Routine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RoutineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoutinesTable::configure($table);
    }
    public static function getEloquentQuery(): Builder
{
    $user = Auth::user();

    return parent::getEloquentQuery()
        ->when($user && $user->role !== 'super_admin', fn (Builder $q) => $q->where('gym_id', $user->gym_id));
}
    public static function getRelations(): array
{
    return [
        \App\Filament\Resources\Routines\RelationManagers\DaysRelationManager::class,
    ];
}
public static function canViewAny(): bool
{
    $user = Auth::user();
    return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
}

public static function canCreate(): bool
{
    $user = Auth::user();
    return $user && in_array($user->role, ['admin', 'coach']);
}

public static function canEdit($record): bool
{
    $user = Auth::user();
    if (! $user) return false;

    // Admin o coach pueden editar rutinas de su gym
    return in_array($user->role, ['super_admin', 'admin', 'coach'])
        && $record->gym_id === $user->gym_id;
}

public static function canDelete($record): bool
{
    $user = Auth::user();
    if (! $user) return false;

    // Recomendación: solo admin borra (historial/importante)
    return in_array($user->role, ['super_admin', 'admin']) && $record->gym_id === $user->gym_id;
}

    public static function getPages(): array
    {
        return [
            'index' => ListRoutines::route('/'),
            'create' => CreateRoutine::route('/create'),
            'edit' => EditRoutine::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
