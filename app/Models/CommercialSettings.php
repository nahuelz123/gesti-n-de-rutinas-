<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CommercialSettings extends Model
{
    protected $guarded = ['id'];

    public const CONFIG_KEYS = [
        'operator_name' => 'legal.operator_name', 'contact_email' => 'legal.contact_email',
        'operator_address' => 'legal.operator_address', 'tax_id' => 'legal.tax_id',
        'manual_payment_instructions' => 'commerce.manual_payment_instructions',
        'coaching_gym_id' => 'commerce.coaching_gym_id',
        'reminders_enabled' => 'commerce.reminders_enabled',
        'reminder_recipient_id' => 'commerce.reminder_recipient_id',
    ];

    protected function casts(): array
    {
        return ['values' => 'array'];
    }

    public static function values(): array
    {
        return Cache::remember('visionfit.commercial-settings', 300, fn () => static::find(1)?->values ?? []);
    }

    public static function saveValues(array $values): void
    {
        DB::transaction(function () use ($values) {
            $settings = static::lockForUpdate()->findOrFail(1);
            $settings->update(['values' => array_replace($settings->values, Arr::only($values, array_keys(self::CONFIG_KEYS)))]);
        });
        Cache::forget('visionfit.commercial-settings');
    }
}
