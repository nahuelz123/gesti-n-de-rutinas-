<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealLog extends Model
{
    protected $fillable = [
        'diet_assignment_id',
        'diet_plan_day_recipe_id',
        'completed',
        'notes',
        'servings_eaten',
        'logged_date',
        'logged_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'logged_date' => 'date',
        'logged_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DietAssignment::class, 'diet_assignment_id');
    }

    public function planDayRecipe(): BelongsTo
    {
        return $this->belongsTo(DietPlanDayRecipe::class, 'diet_plan_day_recipe_id');
    }
}
