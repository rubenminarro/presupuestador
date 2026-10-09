<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'part_id' => $this->part_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity_on_hand' => $this->quantity_on_hand,
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_available' => $this->availableQuantity(),
            'average_cost' => $this->average_cost,
            'stock_value' => $this->stockValue(),
            'is_below_minimum' => $this->whenLoaded(
                'part',
                fn () => $this->isBelowMinimum($this->part->minimum_stock)
            ),
            'last_movement_at' => $this->last_movement_at,
            'part' => new PartResource($this->whenLoaded('part')),
        ];
    }
}
