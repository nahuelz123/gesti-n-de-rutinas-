<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        // super_admin ve todo
        if ($user->role === 'super_admin') {
            return true;
        }

        // admin/coach solo su gym
        return $assignment->gym_id === $user->gym_id
            && in_array($user->role, ['admin', 'coach']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        // super_admin edita todo
        if ($user->role === 'super_admin') {
            return true;
        }

        // Admin puede editar dentro del gym
        if ($user->role === 'admin') {
            return $assignment->gym_id === $user->gym_id;
        }

        // Coach: NO edita
        return false;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        // super_admin borra todo
        if ($user->role === 'super_admin') {
            return true;
        }

        // admin borra dentro del gym
        return $user->role === 'admin'
            && $assignment->gym_id === $user->gym_id;
    }
}
