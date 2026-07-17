<?php

namespace App\Filament\Resources\DietAssignments;

use App\Filament\Resources\DietAssignments\Pages\CreateDietAssignment;
use App\Filament\Resources\DietAssignments\Pages\EditDietAssignment;
use App\Filament\Resources\DietAssignments\Pages\ListDietAssignments;
use App\Filament\Resources\DietAssignments\Schemas\DietAssignmentForm;
use App\Filament\Resources\DietAssignments\Tables\DietAssignmentsTable;
use App\Models\DietAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DietAssignmentResource extends Resource
{
    protected static ?string $model = DietAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Nutrición';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $modelLabel = 'Asignación de dieta';

    protected static ?string $pluralModelLabel = 'Asignaciones de dieta';

    protected static ?string $navigationLabel = 'Asignaciones de dieta';

    public static function form(Schema $schema): Schema
    {
        return DietAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DietAssignmentsTable::configure($table);
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
            'index' => ListDietAssignments::route('/'),
            'create' => CreateDietAssignment::route('/create'),
            'edit' => EditDietAssignment::route('/{record}/edit'),
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

        return in_array($user->role, ['super_admin', 'admin', 'coach'])
            && ($user->role === 'super_admin' || $record->gym_id === $user->gym_id);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        return in_array($user->role, ['super_admin', 'admin'])
            && ($user->role === 'super_admin' || $record->gym_id === $user->gym_id);
    }
}
