<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'sku' => $this->sku,
            'part_number' => $this->part_number,
            'name' => $this->name,
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
            'pivot' => $this->whenPivotLoaded('part_supplier', fn () => [
                'supplier_part_code' => $this->pivot->supplier_part_code,
                'last_cost' => $this->pivot->last_cost,
                'is_preferred' => (bool) $this->pivot->is_preferred,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
