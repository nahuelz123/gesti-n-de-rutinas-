<?php

namespace App\Policies;

use App\Models\FoodItem;
use App\Models\User;

class FoodItemPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach'], true);
    }

    public function view(User $user, FoodItem $food): bool
    {
        return $food->is_global || $this->update($user, $food);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FoodItem $food): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return in_array($user->role, ['admin', 'coach'], true)
            && ! $food->is_global
            && $user->gym_id !== null
            && $food->creator?->gym_id === $user->gym_id;
    }

    public function delete(User $user, FoodItem $food): bool
    {
        return $this->update($user, $food);
    }
}
