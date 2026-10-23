<?php

namespace App\Services;

use App\Models\CommercialSettings;

class CommercialPricing
{
    public function products(): array
    {
        return config('commerce.plans') + ['rutina-puntual' => [
            'name' => 'Rutina puntual', 'audience' => 'personal', 'kind' => 'one_off',
            'price' => config('commerce.one_off_routine_price'),
        ]];
    }

    public function promotion(?array $values = null): array
    {
        $stored = ($values ?? CommercialSettings::values())['catalog_promotion'] ?? [];
        $codes = array_keys($this->products());

        return [
            'enabled' => (bool) ($stored['enabled'] ?? false),
            'title' => $stored['title'] ?? 'Promoción especial',
            'description' => $stored['description'] ?? '',
            'percent' => (int) ($stored['percent'] ?? 10),
            'plan_codes' => array_values(array_intersect($stored['plan_codes'] ?? $codes, $codes)),
        ];
    }

    public function items(?array $values = null): array
    {
        $values ??= CommercialSettings::values();
        $promotion = $this->promotion($values);
        $items = $this->products();
        foreach ($items as $code => &$item) {
            $base = (int) ($values['catalog_prices'][$code] ?? round($item['price'] * 100));
            $percent = $promotion['enabled'] && in_array($code, $promotion['plan_codes'], true) ? $promotion['percent'] : 0;
            $item['base_price_cents'] = $base;
            $item['price_cents'] = intdiv($base * (100 - $percent) + 50, 100);
            $item['discount_percent'] = $percent;
            $item['price'] = $item['price_cents'] / 100;
        }

        return $items;
    }

    public function version(): int
    {
        return (int) (CommercialSettings::values()['catalog_version'] ?? 0);
    }
}
