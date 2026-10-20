<?php

namespace App\Listeners;

use App\Models\PushSubscription;
use Illuminate\Auth\Events\Logout;

class RemovePushOnLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user && request()->hasSession()) {
            $id = request()->session()->pull('push_subscription_id');
            if ($id) {
                PushSubscription::whereKey($id)->where('user_id', $event->user->getAuthIdentifier())->delete();
            }
        }
    }
}
