<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'part_id' => $this->part_id,
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
            'document_number' => $this->document_number,
            'part' => $this->whenLoaded('part', fn () => [
                'id' => $this->part->id,
                'code' => $this->part->code,
                'name' => $this->part->name,
            ]),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
