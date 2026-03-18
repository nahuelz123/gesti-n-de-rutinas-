<?php

namespace App\Filament\Resources\RoutineDays;

use App\Filament\Resources\RoutineDays\Pages\CreateRoutineDay;
use App\Filament\Resources\RoutineDays\Pages\EditRoutineDay;
use App\Filament\Resources\RoutineDays\Pages\ListRoutineDays;
use App\Filament\Resources\RoutineDays\Schemas\RoutineDayForm;
use App\Filament\Resources\RoutineDays\Tables\RoutineDaysTable;
use App\Models\RoutineDay;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RoutineDayResource extends Resource
{
    protected static ?string $model = RoutineDay::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return RoutineDayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoutineDaysTable::configure($table);
    }
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->when(
                $user,
                fn(Builder $q) =>
                $q->whereHas('routine', fn(Builder $rq) => $rq->where('gym_id', $user->gym_id))
            );
    }
public static function getRelations(): array
{
    return [
        \App\Filament\Resources\RoutineDays\RelationManagers\ExercisesRelationManager::class,
    ];
}

    public static function getPages(): array
    {
        return [
            'index' => ListRoutineDays::route('/'),
            'create' => CreateRoutineDay::route('/create'),
            'edit' => EditRoutineDay::route('/{record}/edit'),
        ];
    }
}
