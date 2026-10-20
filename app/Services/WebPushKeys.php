<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\VAPID;

class WebPushKeys
{
    public function get(): array
    {
        $stored = DB::table('web_push_keys')->where('id', 1)->value('keys');
        if ($stored === null) {
            // Unique singleton insert handles simultaneous first requests across web/worker.
            DB::table('web_push_keys')->insertOrIgnore(['id' => 1, 'keys' => Crypt::encryptString(json_encode(VAPID::createVapidKeys(), JSON_THROW_ON_ERROR))]);
            $stored = DB::table('web_push_keys')->where('id', 1)->value('keys');
        }

        return json_decode(Crypt::decryptString($stored), true, flags: JSON_THROW_ON_ERROR);
    }
}
