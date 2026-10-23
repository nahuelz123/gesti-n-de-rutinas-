<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialOperation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'result' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('La bitácora comercial no se edita.'));
        static::deleting(fn () => throw new \LogicException('La bitácora comercial no se borra.'));
    }
}
