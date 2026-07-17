<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlanDay extends Model
{
    protected $fillable = [
        'diet_plan_id',
        'day_of_week',
        'notes',
    ];

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(DietPlanDayRecipe::class)->orderBy('order');
    }
}
