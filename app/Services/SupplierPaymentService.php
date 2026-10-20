<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\SupplierPaymentException;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pagos a proveedores contra compras recibidas (contado o crédito): varios
 * pagos hasta cubrir el total. Orden de bloqueo: compra → pago.
 */
class SupplierPaymentService
{
    private const RELATIONS = ['purchase', 'supplier'];

    public function register(Purchase $purchase, array $data, User $user): SupplierPayment
    {
        return DB::transaction(function () use ($purchase, $data, $user) {

            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== PurchaseStatus::RECEIVED) {
                throw new SupplierPaymentException(
                    "La compra {$purchase->status->label()} no admite pagos; solo las recibidas.",
                    422,
                    'PURCHASE_NOT_PAYABLE'
                );
            }

            $balance = $purchase->balance();

            if (bccomp($balance, '0', 2) <= 0) {
                throw new SupplierPaymentException(
                    'La compra ya está pagada.',
                    422,
                    'PURCHASE_ALREADY_PAID'
                );
            }

            $amount = bcadd((string) $data['amount'], '0', 2);

            if (bccomp($amount, $balance, 2) > 0) {
                throw new SupplierPaymentException(
                    "El pago ({$amount}) supera el saldo de la compra ({$balance}).",
                    422,
                    'SUPPLIER_PAYMENT_EXCEEDS_BALANCE'
                );
            }

            $paymentDate = isset($data['payment_date']) ? Carbon::parse($data['payment_date']) : now();

            if ($paymentDate->copy()->startOfDay()->lt($purchase->supplier_document_date)) {
                throw new SupplierPaymentException(
                    "La fecha del pago no puede ser anterior a la factura del proveedor ({$purchase->supplier_document_date->toDateString()}).",
                    422,
                    'SUPPLIER_PAYMENT_DATE_BEFORE_DOCUMENT'
                );
            }

            $method = PaymentMethod::from($data['method']);

            $payment = $purchase->payments()->create([
                'code' => $this->generateCode(),
                'supplier_id' => $purchase->supplier_id,
                'payment_date' => $paymentDate->toDateString(),
                'amount' => $amount,
                'method' => $method,
                'reference' => $method === PaymentMethod::CASH ? null : ($data['reference'] ?? null),
                'bank' => in_array($method, [PaymentMethod::TRANSFER, PaymentMethod::CHECK], true) ? ($data['bank'] ?? null) : null,
                'supplier_receipt_number' => $data['supplier_receipt_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PaymentStatus::REGISTERED,
                'created_by' => $user->id,
            ]);

            return $payment->load(self::RELATIONS);
        });
    }

    public function cancel(SupplierPayment $payment, string $reason, User $user): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $reason, $user) {

            Purchase::withTrashed()->whereKey($payment->purchase_id)->lockForUpdate()->firstOrFail();
            $payment = SupplierPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentStatus::REGISTERED) {
                throw new SupplierPaymentException(
                    'El pago al proveedor ya está anulado.',
                    422,
                    'SUPPLIER_PAYMENT_NOT_CANCELLABLE'
                );
            }

            $payment->update([
                'status' => PaymentStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $reason,
            ]);

            return $payment->load(self::RELATIONS);
        });
    }

    /**
     * Egresos a proveedores: pagos vigentes por medio de pago en el período.
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $inPeriod = fn ($query) => $query
            ->whereDate('payment_date', '>=', $from->toDateString())
            ->whereDate('payment_date', '<=', $to->toDateString());

        $rows = SupplierPayment::query()
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
            'cancelled_count' => SupplierPayment::query()
                ->where('status', PaymentStatus::CANCELLED)
                ->tap($inPeriod)
                ->count(),
        ];
    }

    /**
     * Cuentas por pagar a hoy: compras recibidas con saldo, agrupadas por
     * proveedor, separando lo vencido (vencimiento, o fecha de la factura al
     * contado, anterior a hoy) de lo que aún no vence. Con un proveedor
     * se detallan sus compras.
     */
    public function accountsPayable(?int $supplierId = null): array
    {
        $today = now()->startOfDay();

        $rows = Purchase::query()
            ->where('status', PurchaseStatus::RECEIVED)
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->withSum(['payments as paid_total' => fn ($q) => $q->registered()], 'amount')
            ->with('supplier')
            ->orderBy('id')
            ->get()
            ->map(fn (Purchase $purchase) => [
                'purchase' => $purchase,
                'balance' => $purchase->balance(),
                'overdue' => $purchase->payableDueDate()->lt($today),
            ])
            ->filter(fn ($row) => bccomp($row['balance'], '0', 2) > 0);

        $suppliers = $rows
            ->groupBy(fn ($row) => $row['purchase']->supplier_id)
            ->map(function (Collection $group) use ($supplierId, $today) {
                $supplier = $group->first()['purchase']->supplier;

                $data = [
                    'supplier_id' => $supplier->id,
                    'name' => $supplier->name,
                    'tax_id' => $supplier->tax_id,
                    'purchases_count' => $group->count(),
                    ...$this->split($group),
                    'oldest_due_date' => $group->map(fn ($row) => $row['purchase']->payableDueDate())->min()?->toDateString(),
                ];

                if ($supplierId) {
                    $data['purchases'] = $group->map(fn ($row) => [
                        'id' => $row['purchase']->id,
                        'code' => $row['purchase']->code,
                        'supplier_document_number' => $row['purchase']->supplier_document_number,
                        'supplier_document_date' => $row['purchase']->supplier_document_date->toDateString(),
                        'condition' => $row['purchase']->condition->value,
                        'due_date' => $row['purchase']->payableDueDate()->toDateString(),
                        'days_overdue' => $row['overdue'] ? (int) $row['purchase']->payableDueDate()->diffInDays($today) : 0,
                        'total' => $row['purchase']->total,
                        'paid_total' => $row['purchase']->paidTotal(),
                        'balance' => $row['balance'],
                        'payment_status' => $row['purchase']->paymentStatus()->value,
                    ])->values();
                }

                return $data;
            })
            ->sortByDesc(fn ($row) => (float) $row['balance'])
            ->values();

        return [
            'as_of' => $today->toDateString(),
            ...$this->split($rows),
            'suppliers' => $suppliers,
        ];
    }

    /**
     * Saldo total, vencido y por vencer de un grupo de compras con saldo.
     */
    private function split(Collection $rows): array
    {
        $sum = fn (Collection $rows) => $rows->reduce(fn (string $sum, $row) => bcadd($sum, $row['balance'], 2), '0.00');

        return [
            'balance' => $sum($rows),
            'overdue' => $sum($rows->where('overdue', true)),
            'not_due' => $sum($rows->where('overdue', false)),
        ];
    }

    private function generateCode(): string
    {
        $nextId = (SupplierPayment::lockForUpdate()->max('id') ?? 0) + 1;

        return 'PAG-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
