<?php

namespace App\Policies;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GymPolicy
{
    public function viewAny(User $user): bool
{
    return in_array($user->role, ['super_admin','admin']);
}

public function view(User $user, Gym $gym): bool
{
    if ($user->role === 'super_admin') return true;
    return $user->role === 'admin' && $user->gym_id === $gym->id;
}

public function create(User $user): bool
{
    return $user->role === 'super_admin';
}

public function update(User $user, Gym $gym): bool
{
    return $user->role === 'super_admin';
}

public function delete(User $user, Gym $gym): bool
{
    return $user->role === 'super_admin';
}

}
