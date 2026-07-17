<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlanDayRecipe extends Model
{
    protected $fillable = [
        'diet_plan_day_id',
        'recipe_id',
        'meal_type',
        'order',
        'notes',
        'servings',
    ];

    public function dietPlanDay(): BelongsTo
    {
        return $this->belongsTo(DietPlanDay::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MealLog::class);
    }
}
