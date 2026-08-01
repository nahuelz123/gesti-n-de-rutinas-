<?php

namespace App\Filament\Resources\FoodItems;

use App\Filament\Resources\FoodItems\Pages\CreateFoodItem;
use App\Filament\Resources\FoodItems\Pages\EditFoodItem;
use App\Filament\Resources\FoodItems\Pages\ListFoodItems;
use App\Filament\Resources\FoodItems\Schemas\FoodItemForm;
use App\Filament\Resources\FoodItems\Tables\FoodItemsTable;
use App\Models\FoodItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FoodItemResource extends Resource
{
    protected static ?string $model = FoodItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|\UnitEnum|null $navigationGroup = 'Nutrición';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Alimento';

    protected static ?string $pluralModelLabel = 'Alimentos (diario libre)';

    protected static ?string $navigationLabel = 'Alimentos';

    public static function form(Schema $schema): Schema
    {
        return FoodItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoodItemsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->when($user && $user->role !== 'super_admin', function (Builder $q) use ($user) {
                $q->where(function (Builder $qq) use ($user) {
                    $qq->where('is_global', true)
                       ->orWhereHas('creator', fn (Builder $cq) => $cq->where('gym_id', $user->gym_id));
                });
            });
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFoodItems::route('/'),
            'create' => CreateFoodItem::route('/create'),
            'edit' => EditFoodItem::route('/{record}/edit'),
        ];
    }
}
