<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeMealLog extends Model
{
    protected $fillable = [
        'client_id',
        'food_item_id',
        'custom_name',
        'quantity_grams',
        'calories',
        'protein',
        'carbs',
        'fat',
        'logged_date',
        'logged_at',
    ];

    protected $casts = [
        'quantity_grams' => 'decimal:1',
        'calories' => 'decimal:1',
        'protein' => 'decimal:1',
        'carbs' => 'decimal:1',
        'fat' => 'decimal:1',
        'logged_date' => 'date',
        'logged_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function foodItem(): BelongsTo
    {
        return $this->belongsTo(FoodItem::class);
    }

    public function displayName(): string
    {
        return $this->foodItem?->name ?? $this->custom_name ?? 'Alimento';
    }
}
