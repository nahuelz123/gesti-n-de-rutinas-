<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class VisionFitStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return match ($user->role) {
            'super_admin' => $this->superAdminStats(),
            'admin' => $this->adminStats($user->gym_id),
            'coach' => $this->coachStats($user),
            default => [],
        };
    }

    protected function superAdminStats(): array
    {
        return [
            Stat::make('Gimnasios activos', Gym::where('active', true)->count())
                ->description('Total de gimnasios: '.Gym::count())
                ->color('success'),

            Stat::make('Usuarios totales', User::count())
                ->description('Clientes: '.User::where('role', 'client')->count())
                ->color('primary'),

            Stat::make('Rutinas creadas', Routine::count())
                ->color('warning'),

            Stat::make('Asignaciones activas', Assignment::where('status', 'active')->count())
                ->description('Total de asignaciones: '.Assignment::count())
                ->color('success'),
        ];
    }

    protected function adminStats(?int $gymId): array
    {
        return [
            Stat::make('Clientes', User::where('gym_id', $gymId)->where('role', 'client')->count())
                ->color('primary'),

            Stat::make('Coaches', User::where('gym_id', $gymId)->where('role', 'coach')->count())
                ->color('warning'),

            Stat::make('Rutinas creadas', Routine::where('gym_id', $gymId)->count())
                ->color('success'),

            Stat::make('Asignaciones activas', Assignment::where('gym_id', $gymId)->where('status', 'active')->count())
                ->description('Total: '.Assignment::where('gym_id', $gymId)->count())
                ->color('success'),
        ];
    }

    protected function coachStats(User $user): array
    {
        $clientesAsignados = Assignment::where('gym_id', $user->gym_id)
            ->where('assigned_by_id', $user->id)
            ->distinct('client_id')
            ->count('client_id');

        return [
            Stat::make('Mis clientes', $clientesAsignados)
                ->color('primary'),

            Stat::make('Rutinas creadas por mí', Routine::where('coach_id', $user->id)->count())
                ->color('warning'),

            Stat::make('Asignaciones activas', Assignment::where('gym_id', $user->gym_id)
                ->where('assigned_by_id', $user->id)
                ->where('status', 'active')
                ->count())
                ->color('success'),
        ];
    }
}
