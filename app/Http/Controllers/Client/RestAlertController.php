<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\PushSubscription;
use App\Models\RestAlert;
use App\Services\WebPushKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RestAlertController extends Controller
{
    public function key(WebPushKeys $keys)
    {
        return response()->json(['publicKey' => $keys->get()['publicKey']])->header('Cache-Control', 'private, no-store');
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048', function ($attribute, $value, $fail) {
                $url = parse_url($value);
                if (! $url || ($url['scheme'] ?? '') !== 'https' || ! in_array($url['host'] ?? '', ['web.push.apple.com', 'fcm.googleapis.com', 'updates.push.services.mozilla.com'], true) || isset($url['user']) || isset($url['pass']) || isset($url['port']) || isset($url['fragment'])) {
                    $fail('Proveedor de notificaciones no permitido.');
                }
            }],
            'keys.p256dh' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{87}$/'],
            'keys.auth' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{22}$/'],
        ]);
        $subscription = DB::transaction(function () use ($request, $data) {
            // Lock the user to bound new subscriptions from concurrent requests.
            DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
            $hash = hash('sha256', $data['endpoint']);
            PushSubscription::query()->insertOrIgnore(['user_id' => $request->user()->id, 'endpoint_hash' => $hash, 'subscription' => Crypt::encryptString(json_encode($data)), 'created_at' => now(), 'updated_at' => now(), 'last_used_at' => now()]);
            $subscription = PushSubscription::where('endpoint_hash', $hash)->lockForUpdate()->firstOrFail();
            if ($subscription->user_id !== $request->user()->id) {
                // The existing endpoint + encryption keys prove device possession. Cancel
                // the previous account's deadlines before rebinding this browser device.
                abort_unless(hash_equals(json_encode($subscription->subscription['keys']), json_encode($data['keys'])), 403);
                RestAlert::where('push_subscription_id', $subscription->id)->update(['status' => 'cancelled']);
            }
            $subscription->update(['user_id' => $request->user()->id, 'subscription' => $data, 'last_used_at' => now()]);
            PushSubscription::where('user_id', $request->user()->id)->orderByDesc('last_used_at')->skip(20)->take(100)->get()->each->delete();

            return $subscription;
        }, 3);

        $request->session()->put('push_subscription_id', $subscription->id);

        return response()->json(['id' => $subscription->id]);
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);
        PushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }

    public function schedule(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->client_id === $request->user()->id && $assignment->gym_id === $request->user()->gym_id, 404);
        $data = $request->validate(['subscription_id' => ['required', 'integer'], 'sequence' => ['required', 'integer', 'min:1', 'max:9007199254740991'], 'deadline' => ['present', 'nullable', 'integer', 'min:'.now()->subSeconds(90)->getTimestampMs(), 'max:'.now()->addSeconds(3605)->getTimestampMs()]]);
        DB::transaction(function () use ($request, $assignment, $data) {
            $subscription = PushSubscription::where('id', $data['subscription_id'])->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            $alert = RestAlert::firstOrCreate(['push_subscription_id' => $subscription->id, 'assignment_id' => $assignment->id], ['sequence' => 0, 'status' => 'cancelled']);
            $alert = RestAlert::whereKey($alert->id)->lockForUpdate()->firstOrFail();
            if ($data['sequence'] <= $alert->sequence) {
                return;
            }
            $alert->update(['sequence' => $data['sequence'], 'due_at' => $data['deadline'] ? Carbon::createFromTimestampMs($data['deadline'], config('app.timezone')) : null, 'status' => $data['deadline'] && $data['deadline'] > now()->getTimestampMs() ? 'pending' : 'cancelled', 'attempts' => 0, 'next_attempt_at' => null]);
            $subscription->update(['last_used_at' => now()]);
        }, 3);

        return response()->json(['ok' => true]);
    }
}
