<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['gym_id', 'sender_id', 'recipient_id', 'body', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Todos los mensajes intercambiados entre dos usuarios (en cualquier dirección).
     */
    public function scopeBetweenUsers(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where(function (Builder $q) use ($userA, $userB) {
            $q->where(function (Builder $qq) use ($userA, $userB) {
                $qq->where('sender_id', $userA)->where('recipient_id', $userB);
            })->orWhere(function (Builder $qq) use ($userA, $userB) {
                $qq->where('sender_id', $userB)->where('recipient_id', $userA);
            });
        });
    }
}
