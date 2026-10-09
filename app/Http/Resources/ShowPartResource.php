<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowPartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'sku' => $this->sku,
            'part_number' => $this->part_number,
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'part_category_id' => $this->part_category_id,
            'unit' => [
                'value' => $this->unit?->value,
                'label' => $this->unit?->label(),
            ],
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'minimum_stock' => $this->minimum_stock,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'category' => new PartCategoryResource(
                $this->whenLoaded('category')
            ),
            'suppliers' => SupplierResource::collection(
                $this->whenLoaded('suppliers')
            ),
            'stock' => $this->whenLoaded('stock', fn () => $this->stock ? [
                'on_hand' => $this->stock->quantity_on_hand,
                'reserved' => $this->stock->quantity_reserved,
                'available' => $this->stock->availableQuantity(),
                'average_cost' => $this->stock->average_cost,
                'is_below_minimum' => $this->stock->isBelowMinimum($this->minimum_stock),
                'last_movement_at' => $this->stock->last_movement_at,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
