<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_order_id' => $this->work_order_id,
            'budget_item_id' => $this->budget_item_id,
            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->label(),
            ],
            'description' => $this->description,
            'quantity' => $this->quantity,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}