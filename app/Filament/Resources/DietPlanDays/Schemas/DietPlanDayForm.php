<?php

namespace App\Filament\Resources\DietPlanDays\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DietPlanDayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('diet_plan_id')
                    ->label('Plan de dieta')
                    ->relationship('dietPlan', 'title', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->when(Auth::user()?->role !== 'super_admin', fn (Builder $query): Builder => $query
                            ->where('gym_id', Auth::user()?->gym_id ?? 0)))
                    ->rules(fn (): array => [
                        Rule::exists('diet_plans', 'id')
                            ->whereNull('deleted_at')
                            ->when(Auth::user()?->role !== 'super_admin', fn ($rule) => $rule
                                ->where('gym_id', Auth::user()?->gym_id ?? 0)),
                    ])
                    ->required(),

                Select::make('day_of_week')
                    ->label('Día')
                    ->native(false)
                    ->options([
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miércoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo',
                    ])
                    ->required(),

                Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }
}
