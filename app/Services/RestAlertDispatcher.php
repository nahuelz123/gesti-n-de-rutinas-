<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\PushSubscription;
use App\Models\RestAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class RestAlertDispatcher
{
    public function dispatchDue(): void
    {
        RestAlert::where('status', 'pending')->where('due_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('due_at')->limit(100)->pluck('id')->each(fn ($id) => $this->dispatch($id));
    }

    public function dispatch(int $id): void
    {
        // All mutations take the subscription lock first, then alert. Holding these
        // through the bounded HTTP call serializes restart/cancel against delivery.
        $subscriptionId = RestAlert::whereKey($id)->value('push_subscription_id');
        if (! $subscriptionId) {
            return;
        }
        DB::transaction(function () use ($id, $subscriptionId) {
            $subscription = PushSubscription::whereKey($subscriptionId)->lockForUpdate()->first();
            $alert = RestAlert::whereKey($id)->lockForUpdate()->first();
            if (! $subscription || ! $alert || $alert->status !== 'pending' || $alert->due_at->isFuture() || $alert->next_attempt_at?->isFuture()) {
                return;
            }
            $assignment = Assignment::find($alert->assignment_id);
            $user = User::find($subscription->user_id);
            if (! $assignment || ! $user || $user->role !== 'client' || $assignment->client_id !== $user->id || $assignment->gym_id !== $user->gym_id) {
                $alert->update(['status' => 'cancelled']);

                return;
            }
            if ($alert->due_at->lt(now()->subSeconds(90)) || $alert->attempts >= 4) {
                $alert->update(['status' => 'expired']);

                return;
            }
            try {
                $result = app(PushDelivery::class)->send($subscription, 'rest-'.$alert->id.'-'.$alert->sequence);
            } catch (Throwable $exception) {
                // Do not log provider request exceptions: their URI contains an endpoint secret.
                $result = 'retry';
            }
            if ($result === 'expired') {
                $subscription->delete();

                return;
            }
            $attempts = $alert->attempts + 1;
            $alert->update(['attempts' => $attempts, 'status' => $result === 'sent' ? 'sent' : ($attempts >= 4 ? 'expired' : 'pending'), 'next_attempt_at' => $result === 'sent' ? null : now()->addSeconds(5 * $attempts)]);
        }, 3);
    }

    public function prune(): void
    {
        RestAlert::where('updated_at', '<', now()->subDays(7))->delete();
        PushSubscription::where('last_used_at', '<', now()->subDays(90))->delete();
    }
}
