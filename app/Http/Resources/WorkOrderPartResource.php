<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderPartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_order_id' => $this->work_order_id,
            'work_order_item_id' => $this->work_order_item_id,
            'is_budgeted' => $this->work_order_item_id !== null,
            'part_id' => $this->part_id,
            'part' => $this->whenLoaded('part', fn () => [
                'id' => $this->part->id,
                'code' => $this->part->code,
                'name' => $this->part->name,
                'unit' => [
                    'value' => $this->part->unit?->value,
                    'label' => $this->part->unit?->label(),
                ],
            ]),
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'quantity' => $this->quantity,
            'quantity_consumed' => $this->quantity_consumed,
            'quantity_returned' => $this->quantity_returned,
            'quantity_net' => $this->netQuantity(),
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price,
            'total_cost' => $this->totalCost(),
            'total_price' => $this->totalPrice(),
            'notes' => $this->notes,
            'added_by' => $this->added_by,
            'consumed_by' => $this->consumed_by,
            'consumed_at' => $this->consumed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
