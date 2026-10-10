<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'budget_id' => $this->budget_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'part_id' => $this->part_id,
            'part' => $this->whenLoaded('part', fn () => $this->part ? [
                'id' => $this->part->id,
                'code' => $this->part->code,
                'name' => $this->part->name,
            ] : null),
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total' => $this->total,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
