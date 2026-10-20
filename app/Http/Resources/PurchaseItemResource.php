<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'part' => $this->whenLoaded('part', fn () => [
                'id' => $this->part?->id,
                'code' => $this->part?->code,
                'sku' => $this->part?->sku,
                'name' => $this->part?->name,
            ]),
            'quantity' => $this->quantity,
            'tax_rate' => [
                'value' => $this->tax_rate?->value,
                'label' => $this->tax_rate?->label(),
            ],
            'unit_cost' => $this->unit_cost,
            'total' => $this->total,
            'net_unit_cost' => $this->net_unit_cost,
            'net_total' => $this->netTotal(),
            'inventory_movement_id' => $this->inventory_movement_id,
        ];
    }
}
