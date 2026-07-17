<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'photo_url',
        'video_url',
        'calories',
        'protein',
        'carbs',
        'fat',
        'prep_time',
        'servings',
        'meal_type',
        'is_global',
        'created_by_id',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'protein' => 'decimal:1',
        'carbs' => 'decimal:1',
        'fat' => 'decimal:1',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('order');
    }

    public function instructions(): HasMany
    {
        return $this->hasMany(RecipeInstruction::class)->orderBy('step');
    }

    public function dietPlanDayRecipes(): HasMany
    {
        return $this->hasMany(DietPlanDayRecipe::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Recipe $recipe) {
            $user = Auth::user();
            if (! $user) return;

            // Solo super_admin puede crear recetas globales
            if ($user->role !== 'super_admin') {
                $recipe->is_global = false;
            }

            if (! $recipe->created_by_id) {
                $recipe->created_by_id = $user->id;
            }
        });
    }
}
