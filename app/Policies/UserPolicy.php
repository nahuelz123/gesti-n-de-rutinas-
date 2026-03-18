<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
{
    return in_array($user->role, ['super_admin','admin','coach']);
}

public function view(User $user, User $model): bool
{
    if ($user->role === 'super_admin') return true;

    if ($user->gym_id !== $model->gym_id) return false;

    if ($user->role === 'coach') {
        return $model->role === 'client';
    }

    return $user->role === 'admin';
}

public function create(User $user): bool
{
    return in_array($user->role, ['super_admin','admin']);
}

public function update(User $user, User $model): bool
{
    if ($user->role === 'super_admin') return true;
    if ($user->gym_id !== $model->gym_id) return false;

    return $user->role === 'admin';
}

public function delete(User $user, User $model): bool
{
    if ($user->role === 'super_admin') return true;
    if ($user->gym_id !== $model->gym_id) return false;

    return $user->role === 'admin';
}

}
