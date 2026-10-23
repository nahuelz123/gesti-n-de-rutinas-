<?php

namespace App\Http\Controllers;

use App\Models\CommercialPayment;
use App\Models\CommercialRefund;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommercialExportController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $range = $request->validate(['from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:to'], 'to' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today']]);
        $from = $range['from'] ?? now()->startOfMonth()->toDateString();
        $to = $range['to'] ?? now()->toDateString();
        if ($from > $to || Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 366) {
            throw ValidationException::withMessages(['from' => 'Elegí un rango ordenado de hasta 366 días.']);
        }

        return response()->streamDownload(function () use ($from, $to) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Fecha', 'Tipo', 'Titular', 'Correo', 'Plan', 'Importe ARS', 'Moneda', 'Referencia', 'Registro', 'Referencia fiscal'], ',', '"', '');
            CommercialPayment::with('subscription.user')->where('source', 'manual')->whereDate('paid_at', '>=', $from)->whereDate('paid_at', '<=', $to)->orderBy('id')->chunkById(100, function ($payments) use ($stream) {
                foreach ($payments as $payment) {
                    $this->row($stream, $payment, 'Cobro', $payment->paid_at?->toDateString(), $payment->amount_cents, $payment->reference, $payment->public_id);
                }
            });
            CommercialRefund::with('payment.subscription.user')->whereDate('performed_on', '>=', $from)->whereDate('performed_on', '<=', $to)->orderBy('id')->chunkById(100, function ($refunds) use ($stream) {
                foreach ($refunds as $refund) {
                    $this->row($stream, $refund->payment, 'Reintegro', $refund->performed_on->toDateString(), -$refund->amount_cents, $refund->reason, $refund->public_id);
                }
            });
            fclose($stream);
        }, 'visionfit-movimientos-'.$from.'-'.$to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function row($stream, CommercialPayment $payment, string $type, ?string $date, int $amount, ?string $reference, string $id): void
    {
        $owner = $payment->subscription?->user;
        fputcsv($stream, [$date, $type, $this->safe($owner?->name), $this->safe($owner?->email), $this->safe(config('commerce.plans.'.$payment->plan_code.'.name', $payment->plan_code)), number_format($amount / 100, 2, '.', ''), 'ARS', $this->safe($reference), $id, $this->safe($payment->fiscal_reference)], ',', '"', '');
    }

    private function safe(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
