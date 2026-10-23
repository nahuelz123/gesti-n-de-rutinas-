<?php

namespace App\Services;

use App\Models\CommercialOperation;
use App\Models\CommercialSettings;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommercialCatalogWriter
{
    public function __construct(private CommercialPricing $pricing) {}

    public function save(User $actor, array $data): void
    {
        abort_unless($actor->role === 'super_admin', 403);
        $codes = array_keys($this->pricing->products());
        $rules = [
            'catalog_version' => ['required', 'integer', 'min:0'],
            'prices' => ['required', 'array:'.implode(',', $codes), 'size:'.count($codes)],
            'promotion' => ['required', 'array:title,description,percent,plan_codes'],
            'promotion.title' => ['required', 'string', 'max:120'],
            'promotion.description' => ['nullable', 'string', 'max:280'],
            'promotion.percent' => ['required', 'integer', 'between:1,99'],
            'promotion.plan_codes' => ['required', 'array', 'min:1', 'max:'.count($codes)],
            'promotion.plan_codes.*' => ['required', 'string', 'distinct', Rule::in($codes)],
        ];
        foreach ($codes as $code) {
            $rules['prices.'.$code] = ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/D', 'numeric', 'gt:0', 'max:1000000'];
        }
        $validated = Validator::make($data, $rules, [
            'prices.*.regex' => 'Usá pesos sin separador de miles, con punto decimal y hasta dos decimales.',
            'promotion.plan_codes.required' => 'Elegí al menos un plan para la promoción.',
        ])->validate();
        $prices = array_map(fn ($amount) => CommercialAmounts::cents((string) $amount), $validated['prices']);
        $this->write($actor, (int) $validated['catalog_version'], 'catalog_updated', function (array $values) use ($prices, $validated) {
            $values['catalog_prices'] = $prices;
            $values['catalog_promotion'] = [
                'enabled' => (bool) ($values['catalog_promotion']['enabled'] ?? false),
                'title' => $validated['promotion']['title'],
                'description' => $validated['promotion']['description'] ?? '',
                'percent' => (int) $validated['promotion']['percent'],
                'plan_codes' => array_values($validated['promotion']['plan_codes']),
            ];

            return $values;
        });
    }

    public function setEnabled(User $actor, bool $enabled, int $version): void
    {
        abort_unless($actor->role === 'super_admin', 403);
        $this->write($actor, $version, $enabled ? 'promotion_enabled' : 'promotion_disabled', function (array $values) use ($enabled) {
            $values['catalog_promotion'] = array_replace($this->pricing->promotion($values), ['enabled' => $enabled]);

            return $values;
        });
    }

    private function write(User $actor, int $version, string $action, callable $change): void
    {
        DB::transaction(function () use ($actor, $version, $action, $change) {
            $settings = CommercialSettings::lockForUpdate()->findOrFail(1);
            $before = $settings->values ?? [];
            if ((int) ($before['catalog_version'] ?? 0) !== $version) {
                throw ValidationException::withMessages(['catalog_version' => 'El catálogo cambió desde que abriste esta pantalla. Recargala antes de guardar o cambiar el estado.']);
            }
            $after = $change($before);
            if ($this->pricing->promotion($after)['enabled']) {
                foreach ($this->pricing->items($after) as $item) {
                    if ($item['price_cents'] < 1) {
                        throw ValidationException::withMessages(['promotion' => 'El descuento dejaría un precio por debajo de un centavo. Aumentá el precio o reducí el porcentaje.']);
                    }
                }
            }
            $after['catalog_version'] = $version + 1;
            $settings->update(['values' => $after]);
            $fields = ['catalog_prices', 'catalog_promotion', 'catalog_version'];
            CommercialOperation::create([
                'public_id' => (string) Str::uuid(), 'actor_id' => $actor->id, 'actor_name' => $actor->name,
                'action' => $action, 'request_hash' => hash('sha256', json_encode($after)),
                'payload' => ['before' => array_intersect_key($before, array_flip($fields))],
                'result' => ['after' => array_intersect_key($after, array_flip($fields))],
            ]);
        }, 3);
        Cache::forget('visionfit.commercial-settings');
    }
}
