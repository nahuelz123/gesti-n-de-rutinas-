<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssignmentTransition
{
    public function save(array $data, Closure $persist): Model
    {
        return DB::transaction(function () use ($data, $persist): Model {
            // Create and edit serialize transitions for the same client.
            User::query()->whereKey($data['client_id'])->lockForUpdate()->firstOrFail();

            if (($data['status'] ?? null) === 'active') {
                $data['end_date'] = null;
            } elseif (($data['status'] ?? null) === 'completed' && empty($data['end_date'])) {
                $data['end_date'] = now()->toDateString();
            }

            // Validate and save first: rejected changes must leave the active assignment intact.
            $assignment = $persist($data);

            if ($assignment->status === 'active') {
                Assignment::query()
                    ->where('gym_id', $assignment->gym_id)
                    ->where('client_id', $assignment->client_id)
                    ->whereKeyNot($assignment->getKey())
                    ->where('status', 'active')
                    ->update([
                        'status' => 'completed',
                        'end_date' => now()->toDateString(),
                    ]);
            }

            return $assignment;
        });
    }
}
