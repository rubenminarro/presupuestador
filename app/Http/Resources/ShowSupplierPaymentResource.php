<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowSupplierPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'method' => [
                'value' => $this->method?->value,
                'label' => $this->method?->label(),
            ],
            'reference' => $this->reference,
            'bank' => $this->bank,
            'supplier_receipt_number' => $this->supplier_receipt_number,
            'notes' => $this->notes,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
                'tax_id' => $this->supplier?->tax_id,
            ]),
            'purchase' => $this->whenLoaded('purchase', fn () => [
                'id' => $this->purchase->id,
                'code' => $this->purchase->code,
                'supplier_document_number' => $this->purchase->supplier_document_number,
                'condition' => $this->purchase->condition?->value,
                'due_date' => $this->purchase->due_date?->toDateString(),
                'total' => $this->purchase->total,
                'paid_total' => $this->purchase->paidTotal(),
                'balance' => $this->purchase->balance(),
                'payment_status' => $this->purchase->paymentStatus()?->value,
            ]),
            'created_by' => $this->created_by,
            'cancelled_at' => $this->cancelled_at,
            'cancelled_by' => $this->cancelled_by,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
