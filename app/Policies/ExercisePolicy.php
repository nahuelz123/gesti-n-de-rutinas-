<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function view(User $user, Exercise $exercise): bool
    {
        if ($user->role === 'super_admin') return true;
        return $exercise->is_global || $exercise->gym_id === $user->gym_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function update(User $user, Exercise $exercise): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($exercise->is_global) return false;
        return $exercise->gym_id === $user->gym_id;
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($exercise->is_global) return false;
        return $exercise->gym_id === $user->gym_id;
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function restore(User $user, Exercise $exercise): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($exercise->is_global) return false;
        return $exercise->gym_id === $user->gym_id;
    }

    public function forceDelete(User $user, Exercise $exercise): bool
    {
        if ($user->role === 'super_admin') return true;
        return false; // Solo super admin borra físico
    }
}
