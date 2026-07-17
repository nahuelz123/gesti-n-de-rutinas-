<?php

namespace App\Filament\Resources\DietPlans;

use App\Filament\Resources\DietPlans\Pages\CreateDietPlan;
use App\Filament\Resources\DietPlans\Pages\EditDietPlan;
use App\Filament\Resources\DietPlans\Pages\ListDietPlans;
use App\Filament\Resources\DietPlans\Schemas\DietPlanForm;
use App\Filament\Resources\DietPlans\Tables\DietPlansTable;
use App\Models\DietPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DietPlanResource extends Resource
{
    protected static ?string $model = DietPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Nutrición';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Plan de dieta';

    protected static ?string $pluralModelLabel = 'Planes de dieta';

    protected static ?string $navigationLabel = 'Planes de dieta';

    public static function form(Schema $schema): Schema
    {
        return DietPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DietPlansTable::configure($table);
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
            \App\Filament\Resources\DietPlans\RelationManagers\DaysRelationManager::class,
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

        return in_array($user->role, ['super_admin', 'admin', 'coach'])
            && $record->gym_id === $user->gym_id;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        return in_array($user->role, ['super_admin', 'admin']) && $record->gym_id === $user->gym_id;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDietPlans::route('/'),
            'create' => CreateDietPlan::route('/create'),
            'edit' => EditDietPlan::route('/{record}/edit'),
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
