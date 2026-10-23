<?php

namespace App\Services;

use App\Models\CommercialSubscription;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommercialMemberWriter
{
    public function create(Gym $gym, array $data): User
    {
        $this->authorizeGym($gym->id);

        return DB::transaction(function () use ($gym, $data) {
            $locked = Gym::lockForUpdate()->findOrFail($gym->id);
            $this->assertCapacity($locked, $data['role'] ?? 'client', (bool) ($data['commercial_access_enabled'] ?? true));

            return User::create(array_replace($data, ['gym_id' => $locked->id]));
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $gymId = (int) ($data['gym_id'] ?? $user->gym_id);
            $this->authorizeGym($gymId, $user);
            $gym = Gym::lockForUpdate()->findOrFail($gymId);
            $owned = CommercialSubscription::where('user_id', $user->id)->first();
            $expectedRole = $owned?->kind === 'coaching' ? 'client' : 'admin';
            if ($owned && ($gymId !== $user->gym_id || ($data['role'] ?? $user->role) !== $expectedRole || ! ($data['commercial_access_enabled'] ?? true))) {
                throw ValidationException::withMessages(['commercial_access_enabled' => 'El propietario conserva su acceso de administración y facturación.']);
            }
            $this->assertCapacity($gym, $data['role'] ?? $user->role, (bool) ($data['commercial_access_enabled'] ?? $user->commercial_access_enabled), $user->id);
            $user->update($data);

            return $user;
        });
    }

    private function authorizeGym(int $gymId, ?User $member = null): void
    {
        $actor = auth()->user();
        if ($actor && $actor->role !== 'super_admin' && ($gymId !== (int) $actor->gym_id || ($member && (int) $member->gym_id !== (int) $actor->gym_id))) {
            throw ValidationException::withMessages(['gym_id' => 'Solo podés administrar los accesos de tu propio espacio.']);
        }
    }

    private function assertCapacity(Gym $gym, string $role, bool $enabled, ?int $except = null): void
    {
        if (! $gym->billing_managed || ! $enabled || $role === 'super_admin') {
            return;
        }
        $subscription = CommercialSubscription::where('gym_id', $gym->id)->first();
        if (! $subscription?->hasAccess()) {
            throw ValidationException::withMessages(['commercial_access_enabled' => 'El espacio necesita una membresía vigente para habilitar nuevos accesos.']);
        }
        $plan = config('commerce.plans.'.$subscription->currentPlanCode());
        $staff = in_array($role, ['admin', 'coach']);
        $limit = (int) ($plan[$staff ? 'staff' : 'students'] ?? 0);
        if ($subscription->next_plan_code) {
            $pending = config('commerce.plans.'.$subscription->next_plan_code);
            $limit = min($limit, (int) ($pending[$staff ? 'staff' : 'students'] ?? 0));
        }
        $count = User::where('gym_id', $gym->id)->where('commercial_access_enabled', true)
            ->whereIn('role', $staff ? ['admin', 'coach'] : ['client'])->when($except, fn ($query) => $query->where('id', '!=', $except))->count();
        if ($count >= $limit) {
            throw ValidationException::withMessages(['commercial_access_enabled' => 'Alcanzaste el cupo del plan. Archivá un acceso o solicitá un cambio de plan.']);
        }
    }
}
