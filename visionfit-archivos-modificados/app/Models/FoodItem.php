<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FoodItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'calories_per_100g',
        'protein_per_100g',
        'carbs_per_100g',
        'fat_per_100g',
        'is_global',
        'created_by_id',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'calories_per_100g' => 'decimal:1',
        'protein_per_100g' => 'decimal:1',
        'carbs_per_100g' => 'decimal:1',
        'fat_per_100g' => 'decimal:1',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Calcula los macros totales para una cantidad en gramos.
     *
     * @return array{calories: float, protein: float, carbs: float, fat: float}
     */
    public function macrosFor(float $grams): array
    {
        $factor = $grams / 100;

        return [
            'calories' => round(((float) $this->calories_per_100g) * $factor, 1),
            'protein' => round(((float) $this->protein_per_100g) * $factor, 1),
            'carbs' => round(((float) $this->carbs_per_100g) * $factor, 1),
            'fat' => round(((float) $this->fat_per_100g) * $factor, 1),
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FoodItem $item) {
            $user = Auth::user();
            if (! $user) return;

            if ($user->role !== 'super_admin') {
                $item->is_global = false;
            }

            if (! $item->created_by_id) {
                $item->created_by_id = $user->id;
            }
        });
    }
}
