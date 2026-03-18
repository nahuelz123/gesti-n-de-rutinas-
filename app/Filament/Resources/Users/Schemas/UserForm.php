<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();

        // Campo Gym:
        // - super_admin elige gym
        // - otros: gym_id oculto y automático
        $gymField = ($user?->role === 'super_admin')
            ? Select::make('gym_id')
            ->label('Gimnasio')
            ->relationship('gym', 'name')
            ->searchable()
            ->preload()
            ->required()
            : Hidden::make('gym_id')
            ->default(fn() => Auth::user()?->gym_id)
            ->dehydrated()
            ->required();

        // Roles disponibles según quién crea
        $roleOptions = match ($user?->role) {
            'super_admin' => [
                'super_admin' => 'Super Admin',
                'admin' => 'Admin',
                'coach' => 'Coach',
                'client' => 'Client',
            ],
            'admin' => [
                'admin' => 'Admin',
                'coach' => 'Coach',
                'client' => 'Client',
            ],
            'coach' => [
                'client' => 'Client',
            ],
            default => [
                'client' => 'Client',
            ],
        };


        return $schema->components([
            $gymField,

            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255)
                ->rules([
                    fn($record) => Rule::unique('users', 'email')->ignore($record),
                ]),

            Select::make('role')
                ->label('Rol')
                ->options($roleOptions)
                ->default('client')
                ->required()
                ->native(false),

            // Password:
            // - requerido solo en create
            // - si está vacío en edit, no se cambia
            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn(string $operation) => $operation === 'create')
                ->dehydrated(fn($state) => filled($state))
                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                ->maxLength(255),

            Textarea::make('medical_notes')
                ->label('Notas médicas')
                ->rows(5)
                ->columnSpanFull()
                ->visible(fn($get) => $get('role') === 'client')
                ->nullable(),
        ]);
    }
}
