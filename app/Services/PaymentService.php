<?php

namespace App\Services;

use App\Enums\InvoiceCondition;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Pagos contra facturas emitidas (FASE 16). Una factura admite varios pagos
 * (contado o crédito) hasta cubrir su total; los de crédito llevan recibo.
 * Orden de bloqueo: factura → pago.
 */
class PaymentService
{
    public function register(Invoice $invoice, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $user) {

            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->status !== InvoiceStatus::ISSUED) {
                throw new PaymentException(
                    "La factura {$invoice->status->label()} no admite pagos; solo las emitidas.",
                    422,
                    'INVOICE_NOT_PAYABLE'
                );
            }

            $balance = $invoice->balance();

            if (bccomp($balance, '0', 2) <= 0) {
                throw new PaymentException(
                    'La factura ya está pagada.',
                    422,
                    'INVOICE_ALREADY_PAID'
                );
            }

            $amount = bcadd((string) $data['amount'], '0', 2);

            if (bccomp($amount, $balance, 2) > 0) {
                throw new PaymentException(
                    "El pago ({$amount}) supera el saldo de la factura ({$balance}).",
                    422,
                    'PAYMENT_EXCEEDS_BALANCE'
                );
            }

            $paymentDate = isset($data['payment_date']) ? Carbon::parse($data['payment_date']) : now();

            if ($paymentDate->copy()->startOfDay()->lt($invoice->issue_date)) {
                throw new PaymentException(
                    "La fecha del pago no puede ser anterior a la emisión de la factura ({$invoice->issue_date->toDateString()}).",
                    422,
                    'PAYMENT_DATE_BEFORE_ISSUE'
                );
            }

            $method = PaymentMethod::from($data['method']);

            $payment = $invoice->payments()->create([
                ...$this->receipt($invoice),
                'payment_date' => $paymentDate->toDateString(),
                'amount' => $amount,
                'method' => $method,
                'reference' => $method === PaymentMethod::CASH ? null : ($data['reference'] ?? null),
                'bank' => in_array($method, [PaymentMethod::TRANSFER, PaymentMethod::CHECK], true) ? ($data['bank'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'status' => PaymentStatus::REGISTERED,
                'created_by' => $user->id,
            ]);

            return $payment->load('invoice');
        });
    }

    public function cancel(Payment $payment, string $reason, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $user) {

            Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentStatus::REGISTERED) {
                throw new PaymentException(
                    'El pago ya está anulado.',
                    422,
                    'PAYMENT_NOT_CANCELLABLE'
                );
            }

            $payment->update([
                'status' => PaymentStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $reason,
            ]);

            return $payment->load('invoice');
        });
    }

    /**
     * Resumen de caja: pagos vigentes por medio de pago en el período.
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $inPeriod = fn ($query) => $query
            ->whereDate('payment_date', '>=', $from->toDateString())
            ->whereDate('payment_date', '<=', $to->toDateString());

        $rows = Payment::query()
            ->registered()
            ->tap($inPeriod)
            ->selectRaw('method, COUNT(*) as payments_count, SUM(amount) as total')
            ->groupBy('method')
            ->get()
            ->keyBy(fn ($row) => $row->getRawOriginal('method'));

        $byMethod = [];
        $total = '0.00';
        $count = 0;

        foreach (PaymentMethod::cases() as $method) {
            $row = $rows->get($method->value);
            $amount = bcadd((string) ($row?->total ?? '0'), '0', 2);

            $byMethod[] = [
                'method' => $method->value,
                'label' => $method->label(),
                'payments_count' => (int) ($row?->payments_count ?? 0),
                'total' => $amount,
            ];

            $total = bcadd($total, $amount, 2);
            $count += (int) ($row?->payments_count ?? 0);
        }

        return [
            'period' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()],
            'payments_count' => $count,
            'total' => $total,
            'by_method' => $byMethod,
            'cancelled_count' => Payment::query()
                ->where('status', PaymentStatus::CANCELLED)
                ->tap($inPeriod)
                ->count(),
        ];
    }

    /**
     * Recibo correlativo (REC-000001) solo para facturas a crédito.
     * El índice único protege la numeración ante concurrencia.
     */
    private function receipt(Invoice $invoice): array
    {
        if ($invoice->condition !== InvoiceCondition::CREDIT) {
            return [];
        }

        $next = (int) Payment::lockForUpdate()->max('receipt_sequence') + 1;

        return [
            'receipt_sequence' => $next,
            'receipt_number' => 'REC-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT),
        ];
    }
}
