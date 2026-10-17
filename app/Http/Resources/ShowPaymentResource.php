<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'amount_received' => $this->amount_received,
            'change_amount' => $this->change_amount,
            'method' => [
                'value' => $this->method?->value,
                'label' => $this->method?->label(),
            ],
            'reference' => $this->reference,
            'bank' => $this->bank,
            'notes' => $this->notes,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice->id,
                'number' => $this->invoice->number,
                'customer_name' => $this->invoice->customer_name,
                'condition' => $this->invoice->condition?->value,
                'total' => $this->invoice->total,
                'paid_total' => $this->invoice->paidTotal(),
                'balance' => $this->invoice->balance(),
                'payment_status' => $this->invoice->paymentStatus()?->value,
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
