<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClientsWithoutActiveRoutine extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected static ?string $heading = 'Clientes sin rutina activa';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->query(
                User::query()
                    ->where('role', 'client')
                    ->when($user->role !== 'super_admin', fn (Builder $q) => $q->where('gym_id', $user->gym_id))
                    ->whereDoesntHave('assignments', fn (Builder $q) => $q->where('status', 'active'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gym.name')
                    ->label('Gimnasio')
                    ->visible(fn () => $user->role === 'super_admin'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cliente desde')
                    ->date(),
            ])
            ->actions([
                Action::make('assign')
                    ->label('Asignar rutina')
                    ->icon('heroicon-o-plus')
                    ->url(fn (User $record): string => route('filament.admin.resources.assignments.create', [
                        'client_id' => $record->id,
                    ])),
            ])
            ->paginated([5, 10, 25]);
    }
}
