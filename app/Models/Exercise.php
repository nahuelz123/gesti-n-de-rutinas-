<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Support\ExerciseNames;

class Exercise extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gym_id',
        'title',
        'muscle_group',
        'description',
        'tips',
        'video_url',
        'gif_url',
        'is_global',
        'created_by_id',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public function scopeVisibleToGym(Builder $query, ?int $gymId): Builder
    {
        return $query->where(function (Builder $visible) use ($gymId): void {
            $visible->where('is_global', true);
            if ($gymId !== null) {
                $visible->orWhere('gym_id', $gymId);
            }
        });
    }

    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        $terms = ExerciseNames::searchTerms($search);
        if ($terms === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $names) use ($terms): void {
            foreach ($terms as $term) {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
                $names->orWhereRaw("LOWER(exercises.title) LIKE ? ESCAPE '!'", [$pattern]);
            }
        });
    }

    public static function searchOptionsForGym(string $search, ?int $gymId): array
    {
        return static::query()->visibleToGym($gymId)->searchByName($search)
            ->orderBy('title')->limit(50)->get()
            ->mapWithKeys(fn (Exercise $exercise): array => [
                $exercise->id => ($exercise->is_global ? '🌐 ' : '🏠 ').
                    ExerciseNames::displayName($exercise->title).
                    ($exercise->is_global ? ' (Catálogo)' : ' (Mi gym)'),
            ])->all();
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function routineDayExercises(): HasMany
    {
        return $this->hasMany(RoutineDayExercise::class);
    }

    

protected static function booted(): void
{
    static::saving(function (Exercise $exercise) {
        $user = Auth::user();
        if (! $user) return;

        // Si no es super_admin, NO puede crear globales:
        if ($user->role !== 'super_admin') {
            $exercise->gym_id = $user->gym_id;
            $exercise->is_global = false;
        }
    });
}

}
