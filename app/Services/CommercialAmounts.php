<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class CommercialAmounts
{
    public static function format(int $cents): string
    {
        return number_format($cents / 100, $cents % 100 === 0 ? 0 : 2, ',', '.');
    }

    public static function cents(string $amount): int
    {
        if (! preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $amount)) {
            throw ValidationException::withMessages(['amount' => 'Usá un importe positivo, con punto decimal y hasta dos decimales.']);
        }
        $parts = explode('.', $amount);
        $cents = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents < 1) {
            throw ValidationException::withMessages(['amount' => 'El importe debe ser mayor que cero.']);
        }

        return $cents;
    }
}
