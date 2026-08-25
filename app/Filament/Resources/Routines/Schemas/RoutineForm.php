<?php

namespace App\Filament\Resources\Routines\Schemas;

use App\Models\Exercise;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RoutineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('gym_id')
                ->default(fn () => Auth::user()?->gym_id)
                ->dehydrated(),

            Hidden::make('coach_id')
                ->default(fn () => Auth::id())
                ->dehydrated(),

            Section::make('Información de la Rutina')
                ->schema([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Descripción')
                        ->columnSpanFull()
                        ->nullable(),
                ]),

            Section::make('Constructor Visual')
                ->schema([
                    Repeater::make('days')
                        ->relationship('days')
                        ->label('Días de Entrenamiento')
                        ->addActionLabel('+ Agregar día')
                        ->collapsible()
                        ->cloneable()
                        ->orderColumn('day_number')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Día sin título')
                        ->schema([
                            TextInput::make('title')
                                ->label('Nombre del día (Ej: Pecho + Tríceps)')
                                ->required()
                                ->maxLength(255),

                            Repeater::make('exercises')
                                ->relationship('exercises')
                                ->label('Ejercicios')
                                ->addActionLabel('+ Agregar ejercicio')
                                ->collapsible()
                                ->cloneable()
                                ->orderColumn('order')
                                ->itemLabel(fn (array $state): ?string => Exercise::find($state['exercise_id'] ?? null)?->title ?? 'Nuevo ejercicio')
                                ->schema([
                                    Select::make('exercise_id')
                                        ->label('Seleccionar Ejercicio')
                                        ->relationship(
                                            name: 'exercise',
                                            titleAttribute: 'title',
                                            modifyQueryUsing: fn (Builder $query) => $query->where(function ($q) {
                                                $user = Auth::user();
                                                $q->where('is_global', true)
                                                  ->orWhere('gym_id', $user?->gym_id);
                                            })
                                        )
                                        ->getOptionLabelFromRecordUsing(fn (Exercise $record) => ($record->is_global ? '🌐 ' : '🏠 ') . $record->title . ($record->is_global ? ' (Catálogo)' : ' (Mi gym)'))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('sets')
                                                ->label('Series')
                                                ->numeric()
                                                ->required()
                                                ->minValue(1),

                                            TextInput::make('reps')
                                                ->label('Repeticiones (Ej: 8-10)')
                                                ->required()
                                                ->maxLength(20),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('rest')
                                                ->label('Descanso (Ej: 90s)')
                                                ->maxLength(20)
                                                ->nullable(),
                                            
                                            Textarea::make('notes')
                                                ->label('Notas (Opcional)')
                                                ->rows(2)
                                                ->nullable(),
                                        ])
                                ])
                        ])
                ])
        ]);
    }
}
