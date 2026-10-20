<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'purchase_id' => $this->purchase_id,
            'purchase_code' => $this->whenLoaded('purchase', fn () => $this->purchase?->code),
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'method' => [
                'value' => $this->method?->value,
                'label' => $this->method?->label(),
            ],
            'reference' => $this->reference,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
