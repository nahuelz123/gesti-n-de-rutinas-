<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DietAssignment;
use App\Models\MealLog;
use Illuminate\Http\Request;

class MealLogController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'diet_assignment_id' => ['required', 'integer', 'exists:diet_assignments,id'],
            'diet_plan_day_recipe_id' => ['required', 'integer', 'exists:diet_plan_day_recipes,id'],
            'completed' => ['required', 'boolean'],
        ]);

        $assignment = DietAssignment::query()->findOrFail($data['diet_assignment_id']);

        abort_unless($assignment->client_id === $user->id, 403);
        abort_unless($assignment->status === 'active' && $assignment->end_date === null, 403);

        $belongs = $assignment->dietPlan
            ->days()
            ->whereHas('recipes', fn ($q) => $q->where('diet_plan_day_recipes.id', $data['diet_plan_day_recipe_id']))
            ->exists();

        abort_unless($belongs, 403);

        MealLog::updateOrCreate(
            [
                'diet_assignment_id' => $assignment->id,
                'diet_plan_day_recipe_id' => $data['diet_plan_day_recipe_id'],
                'logged_date' => today(),
            ],
            [
                'completed' => $data['completed'],
                'logged_at' => now(),
            ]
        );

        return back()->with('success', $data['completed'] ? '¡Comida registrada!' : 'Comida desmarcada.');
    }
}
