<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExerciseReplacement extends Model
{
    protected $fillable = ['assignment_id', 'routine_day_exercise_id', 'exercise_id', 'performed_on'];

    protected $casts = ['performed_on' => 'date'];

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class)->withTrashed();
    }
}
