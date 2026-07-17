<?php

namespace App\Filament\Resources\DietAssignments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DietAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('gym_id')
                ->default(fn () => Auth::user()?->gym_id)
                ->dehydrated(),

            Hidden::make('assigned_by_id')
                ->default(fn () => Auth::id())
                ->dehydrated(),

            Hidden::make('assigned_at')
                ->default(fn () => now())
                ->dehydrated(),

            Select::make('client_id')
                ->label('Cliente')
                ->required()
                ->searchable()
                ->preload()
                ->relationship(
                    'client',
                    'name',
                    function ($query) {
                        $user = Auth::user();

                        return $query
                            ->when($user, fn ($q) => $q->where('gym_id', $user->gym_id))
                            ->where('role', 'client');
                    }
                ),

            Select::make('diet_plan_id')
                ->label('Plan de dieta')
                ->required()
                ->searchable()
                ->preload()
                ->relationship(
                    'dietPlan',
                    'title',
                    function ($query) {
                        $user = Auth::user();

                        return $query->when($user, fn ($q) => $q->where('gym_id', $user->gym_id));
                    }
                ),

            Select::make('status')
                ->label('Estado')
                ->options([
                    'active' => 'Activa',
                    'paused' => 'Pausada',
                    'completed' => 'Completada',
                ])
                ->default('active')
                ->required()
                ->live()
                ->helperText('Si está Activa, la fecha de fin debería quedar vacía.'),

            DatePicker::make('start_date')
                ->label('Inicio')
                ->nullable(),

            DatePicker::make('end_date')
                ->label('Fin')
                ->nullable()
                ->helperText('Dejá vacío si querés que quede activa.')
                ->disabled(fn ($get) => $get('status') === 'active')
                ->dehydrated()
                ->afterStateHydrated(function ($component, $state, $get) {
                    if ($get('status') === 'active') {
                        $component->state(null);
                    }
                })
                ->afterStateUpdated(function ($state, $set, $get) {
                    if ($get('status') === 'active') {
                        $set('end_date', null);
                    }
                }),

            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull()
                ->nullable(),
        ]);
    }
}
