<?php

namespace App\Filament\Resources\Assignments;

use App\Filament\Resources\Assignments\Pages\CreateAssignment;
use App\Filament\Resources\Assignments\Pages\EditAssignment;
use App\Filament\Resources\Assignments\Pages\ListAssignments;
use App\Filament\Resources\Assignments\Schemas\AssignmentForm;
use App\Filament\Resources\Assignments\Tables\AssignmentsTable;
use App\Models\Assignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
{
    $user = Auth::user();

    return parent::getEloquentQuery()
        ->when($user && $user->role !== 'super_admin', fn (Builder $q) => $q->where('gym_id', $user->gym_id));
}


    public static function getPages(): array
    {
        return [
            'index' => ListAssignments::route('/'),
            'create' => CreateAssignment::route('/create'),
            'edit' => EditAssignment::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function canViewAny(): bool
{
    $user = Auth::user();

    return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
}

public static function canCreate(): bool
{
    $user = Auth::user();

    return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
}

public static function canEdit($record): bool
{
    $user = Auth::user();
    if (! $user) return false;

    // Admin puede editar asignaciones de su gym
    if (in_array($user->role, ['admin', 'coach'])) {
        return $record->gym_id === $user->gym_id;
    }

    // Coach: recomendado que NO edite historial
    return false;
}

public static function canDelete($record): bool
{
    $user = Auth::user();

    return $user
        && in_array($user->role, ['super_admin', 'admin','coach'])
        && $record->gym_id === $user->gym_id;
}

}
