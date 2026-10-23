<?php

namespace App\Http\Middleware;

use App\Models\CommercialSettings;
use Closure;
use Illuminate\Http\Request;

class CommercialConfiguration
{
    public function handle(Request $request, Closure $next)
    {
        $original = [];
        foreach (CommercialSettings::values() as $key => $value) {
            if (isset(CommercialSettings::CONFIG_KEYS[$key]) && filled($value)) {
                $original[CommercialSettings::CONFIG_KEYS[$key]] = config(CommercialSettings::CONFIG_KEYS[$key]);
                config([CommercialSettings::CONFIG_KEYS[$key] => $value]);
            }
        }

        try {
            return $next($request);
        } finally {
            config($original);
        }
    }
}
