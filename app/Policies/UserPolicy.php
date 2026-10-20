<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach'], true);
    }

    public function view(User $user, User $target): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if (! $this->belongsToSameGym($user, $target)) {
            return false;
        }

        return match ($user->role) {
            'admin' => $target->role !== 'super_admin',
            'coach' => $target->role === 'client',
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin'], true);
    }

    public function update(User $user, User $target): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'admin'
            && $target->role !== 'super_admin'
            && $this->belongsToSameGym($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->update($user, $target) && $user->id !== $target->id;
    }

    private function belongsToSameGym(User $user, User $target): bool
    {
        // A missing gym is not a shared tenant.
        return $user->gym_id !== null
            && $target->gym_id !== null
            && $user->gym_id === $target->gym_id;
    }
}
