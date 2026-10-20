<?php

namespace App\Filament\Resources\RoutineDays\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RoutineDayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('routine_id')
                    ->label('Rutina')
                    ->relationship('routine', 'title', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->when(Auth::user()?->role !== 'super_admin', fn (Builder $query): Builder => $query
                            ->where('gym_id', Auth::user()?->gym_id ?? 0)))
                    ->rules(fn (): array => [
                        Rule::exists('routines', 'id')
                            ->whereNull('deleted_at')
                            ->when(Auth::user()?->role !== 'super_admin', fn ($rule) => $rule
                                ->where('gym_id', Auth::user()?->gym_id ?? 0)),
                    ])
                    ->required(),
                TextInput::make('day_number')
                    ->label('Número de día')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
            ]);
    }
}
