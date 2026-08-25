<?php

namespace App\Filament\Resources\Exercises\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ExerciseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('is_global')
                ->label('Ejercicio global (visible para todos los gimnasios)')
                ->default(true)
                ->visible(fn () => Auth::user()?->role === 'super_admin'),

            Hidden::make('gym_id')
                ->default(fn () => Auth::user()?->gym_id)
                ->required(fn () => Auth::user()?->role !== 'super_admin'),

            Hidden::make('created_by_id')
                ->default(fn () => Auth::id()),

            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),

            Select::make('muscle_group')
                ->label('Grupo muscular')
                ->required()
                ->native(false)
                ->options([
                    'pecho' => 'Pecho',
                    'espalda' => 'Espalda',
                    'piernas' => 'Piernas',
                    'gluteos' => 'Glúteos',
                    'hombros' => 'Hombros',
                    'biceps' => 'Bíceps',
                    'triceps' => 'Tríceps',
                    'abdomen' => 'Abdomen',
                    'cardio' => 'Cardio',
                    'fullbody' => 'Full Body',
                ]),

            Textarea::make('description')
                ->label('Descripción')
                ->rows(4)
                ->columnSpanFull()
                ->nullable(),

            Textarea::make('tips')
                ->label('Tips')
                ->rows(4)
                ->columnSpanFull()
                ->nullable(),

            TextInput::make('video_url')
                ->label('Video URL')
                ->url()
                ->maxLength(255)
                ->nullable(),

            TextInput::make('gif_url')
                ->label('GIF animado (URL)')
                ->url()
                ->maxLength(255)
                ->helperText('Se muestra como demostración visual en la app del cliente.')
                ->nullable(),
        ]);
    }
}
