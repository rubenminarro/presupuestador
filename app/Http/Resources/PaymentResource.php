<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->whenLoaded('invoice', fn () => $this->invoice?->number),
            'receipt_number' => $this->receipt_number,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'change_amount' => $this->change_amount,
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
