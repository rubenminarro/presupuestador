<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
                'tax_id' => $this->supplier?->tax_id,
            ]),
            'supplier_document' => [
                'number' => $this->supplier_document_number,
                'date' => $this->supplier_document_date?->toDateString(),
                'stamp_number' => $this->supplier_stamp_number,
            ],
            'condition' => [
                'value' => $this->condition?->value,
                'label' => $this->condition?->label(),
            ],
            'due_date' => $this->due_date?->toDateString(),
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
            'totals' => [
                'exempt' => $this->total_exempt,
                'taxed_5' => $this->total_5,
                'taxed_10' => $this->total_10,
                'iva_5' => $this->iva_5,
                'iva_10' => $this->iva_10,
                'iva_total' => bcadd($this->iva_5, $this->iva_10, 2),
                'total' => $this->total,
                'net_total' => $this->net_total,
            ],
            'payment_summary' => [
                'paid_total' => $this->paidTotal(),
                'balance' => $this->balance(),
                'status' => $this->paymentStatus()?->value,
            ],
            'payments' => SupplierPaymentResource::collection($this->whenLoaded('payments')),
            'notes' => $this->notes,
            'received_at' => $this->received_at,
            'received_by' => $this->received_by,
            'cancelled_at' => $this->cancelled_at,
            'cancelled_by' => $this->cancelled_by,
            'cancellation_reason' => $this->cancellation_reason,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
