<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceStampResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stamp_number' => $this->stamp_number,
            'establishment' => $this->establishment,
            'expedition_point' => $this->expedition_point,
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'next_number' => $this->next_number,
            'next_invoice_number' => $this->formatNumber($this->next_number),
            'is_active' => $this->is_active,
            'is_valid_today' => $this->isValidOn(now()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
