<?php

namespace App\Filament\Resources\Assignments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // gym_id automático (del usuario logueado)
            Hidden::make('gym_id')
                ->default(fn () => Auth::user()?->gym_id)
                ->dehydrated(),

            // quién asignó (auto)
            Hidden::make('assigned_by_id')
                ->default(fn () => Auth::id())
                ->dehydrated(),

            // cuándo se asignó (auto)
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

            Select::make('routine_id')
                ->label('Rutina')
                ->required()
                ->searchable()
                ->preload()
                ->relationship(
                    'routine',
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
                    // si el registro está active, aseguramos que end_date no moleste
                    if ($get('status') === 'active') {
                        $component->state(null);
                    }
                })
                ->afterStateUpdated(function ($state, $set, $get) {
                    // si cambian a active, limpiamos end_date
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
