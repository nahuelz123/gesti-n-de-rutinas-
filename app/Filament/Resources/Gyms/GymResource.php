<?php

namespace App\Filament\Resources\Gyms;

use App\Filament\Resources\Gyms\Pages\CreateGym;
use App\Filament\Resources\Gyms\Pages\EditGym;
use App\Filament\Resources\Gyms\Pages\ListGyms;
use App\Filament\Resources\Gyms\Schemas\GymForm;
use App\Filament\Resources\Gyms\Tables\GymsTable;
use App\Models\Gym;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
   use Illuminate\Database\Eloquent\Builder;

class GymResource extends Resource
{
    protected static ?string $model = Gym::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';
 

public static function getEloquentQuery(): Builder
{
    $user = Auth::user();

    return parent::getEloquentQuery()
        ->when($user && $user->role !== 'super_admin', fn (Builder $q) => $q->where('id', $user->gym_id));
}


    public static function form(Schema $schema): Schema
    {
        return GymForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GymsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
public static function canViewAny(): bool
{
    $user = Auth::user();

    return $user && $user->role === 'super_admin';
}

    public static function getPages(): array
    {
        return [
            'index' => ListGyms::route('/'),
            'create' => CreateGym::route('/create'),
            'edit' => EditGym::route('/{record}/edit'),
        ];
    }
}
