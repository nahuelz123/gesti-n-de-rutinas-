<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecipePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function view(User $user, Recipe $recipe): bool
    {
        if ($user->role === 'super_admin') return true;
        return $recipe->is_global || $recipe->gym_id === $user->gym_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function update(User $user, Recipe $recipe): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($recipe->is_global) return false;
        return $recipe->gym_id === $user->gym_id;
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($recipe->is_global) return false;
        return $recipe->gym_id === $user->gym_id;
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public function restore(User $user, Recipe $recipe): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($recipe->is_global) return false;
        return $recipe->gym_id === $user->gym_id;
    }

    public function forceDelete(User $user, Recipe $recipe): bool
    {
        if ($user->role === 'super_admin') return true;
        return false;
    }
}
