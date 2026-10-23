<?php

namespace App\Services;

use App\Models\CommercialSettings;
use Illuminate\Validation\ValidationException;

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

    public function quote(?array $values = null): string
    {
        $values ??= CommercialSettings::values();
        $prices = array_map(fn ($item) => $item['price_cents'], $this->items($values));

        return hash_hmac('sha256', json_encode([(int) ($values['catalog_version'] ?? 0), $prices], JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public function quotedPlan(string $code, string $quote): array
    {
        // Call inside the agreement transaction; keep the quote stable until it commits.
        $values = CommercialSettings::lockForUpdate()->findOrFail(1)->values ?? [];
        if (! hash_equals($this->quote($values), $quote)) {
            throw ValidationException::withMessages(['catalog_quote' => 'El precio o la oferta cambió. Recargá la pantalla, revisá el nuevo importe y volvé a confirmar.']);
        }

        return $this->items($values)[$code] ?? abort(404);
    }
}
