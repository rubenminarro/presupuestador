<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
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
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'supplier_document_number' => $this->supplier_document_number,
            'supplier_document_date' => $this->supplier_document_date?->toDateString(),
            'condition' => [
                'value' => $this->condition?->value,
                'label' => $this->condition?->label(),
            ],
            'due_date' => $this->due_date?->toDateString(),
            'total' => $this->total,
            'net_total' => $this->net_total,
            'paid_total' => $this->paidTotal(),
            'balance' => $this->balance(),
            'payment_status' => $this->paymentStatus()?->value,
            'received_at' => $this->received_at,
            'created_at' => $this->created_at,
        ];
    }
}
