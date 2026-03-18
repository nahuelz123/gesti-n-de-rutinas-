<?php

namespace App\Policies;

use App\Models\Routine;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RoutinePolicy
{
    public function viewAny(User $user): bool
{
    return in_array($user->role, ['super_admin','admin','coach']);
}

public function view(User $user, Routine $routine): bool
{
    return $user->role === 'super_admin' || $routine->gym_id === $user->gym_id;
}

public function create(User $user): bool
{
    return in_array($user->role, ['super_admin','admin','coach']);
}

public function update(User $user, Routine $routine): bool
{
    return $user->role === 'super_admin'
        || (in_array($user->role, ['admin','coach']) && $routine->gym_id === $user->gym_id);
}

public function delete(User $user, Routine $routine): bool
{
    return $user->role === 'super_admin'
        || ($user->role === 'admin' && $routine->gym_id === $user->gym_id);
}

}
