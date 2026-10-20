<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushDelivery
{
    public function send(PushSubscription $subscription, string $tag): string
    {
        $keys = app(WebPushKeys::class)->get();
        $push = new WebPush(['VAPID' => ['subject' => config('webpush.subject'), 'publicKey' => $keys['publicKey'], 'privateKey' => $keys['privateKey']]], ['TTL' => 60, 'urgency' => 'high'], 10, ['allow_redirects' => false, 'connect_timeout' => 5]);
        $report = $push->sendOneNotification(Subscription::create([...$subscription->subscription, 'contentEncoding' => 'aes128gcm']), json_encode(['tag' => $tag], JSON_THROW_ON_ERROR));
        if ($report->isSubscriptionExpired()) {
            return 'expired';
        }

        return $report->isSuccess() ? 'sent' : 'retry';
    }
}
