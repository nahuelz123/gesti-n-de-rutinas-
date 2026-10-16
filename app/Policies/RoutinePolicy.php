<?php

namespace App\Policies;

use App\Models\Routine;
use App\Models\User;

class RoutinePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach'], true);
    }

    public function view(User $user, Routine $routine): bool
    {
        return $user->role === 'super_admin'
            || (in_array($user->role, ['admin', 'coach'], true) && $this->belongsToSameGym($user, $routine));
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Routine $routine): bool
    {
        return $this->view($user, $routine);
    }

    public function delete(User $user, Routine $routine): bool
    {
        return $user->role === 'super_admin'
            || ($user->role === 'admin' && $this->belongsToSameGym($user, $routine));
    }

    private function belongsToSameGym(User $user, Routine $routine): bool
    {
        return $user->gym_id !== null
            && $routine->gym_id !== null
            && $user->gym_id === $routine->gym_id;
    }
}
