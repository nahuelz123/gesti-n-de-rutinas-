<?php

namespace App\Filament\Resources\DietPlanDays;

use App\Filament\Resources\DietPlanDays\Pages\CreateDietPlanDay;
use App\Filament\Resources\DietPlanDays\Pages\EditDietPlanDay;
use App\Filament\Resources\DietPlanDays\Pages\ListDietPlanDays;
use App\Filament\Resources\DietPlanDays\RelationManagers\RecipesRelationManager;
use App\Filament\Resources\DietPlanDays\Schemas\DietPlanDayForm;
use App\Filament\Resources\DietPlanDays\Tables\DietPlanDaysTable;
use App\Models\DietPlanDay;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DietPlanDayResource extends Resource
{
    protected static ?string $model = DietPlanDay::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'day_of_week';

    protected static ?string $modelLabel = 'Día del plan';

    protected static ?string $pluralModelLabel = 'Días del plan';

    protected static ?string $navigationLabel = 'Días del plan';

    public static function form(Schema $schema): Schema
    {
        return DietPlanDayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DietPlanDaysTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->when(
                $user && $user->role !== 'super_admin',
                fn (Builder $q) => $q->whereHas('dietPlan', fn (Builder $pq) => $pq->where('gym_id', $user->gym_id ?? 0))
            );
    }

    public static function getRelations(): array
    {
        return [
            RecipesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDietPlanDays::route('/'),
            'create' => CreateDietPlanDay::route('/create'),
            'edit' => EditDietPlanDay::route('/{record}/edit'),
        ];
    }
}
