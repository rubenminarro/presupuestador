<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowInventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'part_id' => $this->part_id,
            'warehouse_id' => $this->warehouse_id,
            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->label(),
                'direction' => $this->type?->direction(),
            ],
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'balance_after' => $this->balance_after,
            'average_cost_after' => $this->average_cost_after,
            'supplier_id' => $this->supplier_id,
            'document_number' => $this->document_number,
            'reference' => $this->reference_type ? [
                'type' => $this->reference_type,
                'id' => $this->reference_id,
            ] : null,
            'reason' => $this->reason,
            'part' => new PartResource($this->whenLoaded('part')),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
