<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestAlert extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'next_attempt_at' => 'datetime', 'sequence' => 'integer', 'attempts' => 'integer'];
    }
}
