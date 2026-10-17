<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Repuesto usado en una visita. Costo solo con work-orders.view-economics.
 */
class VehicleHistoryPartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $withEconomics = (bool) $request->user()?->can('work-orders.view-economics');

        return [
            'used_at' => $this->consumed_at,
            'work_order_id' => $this->work_order_id,
            'work_order_code' => $this->workOrder?->code,
            'part_id' => $this->part_id,
            'part_code' => $this->part?->code,
            'name' => $this->part?->name,
            'brand' => $this->part?->brand,
            'quantity' => $this->netQuantity(),
            'unit_price' => $this->unit_price,
            'total_price' => $this->totalPrice(),
            'unit_cost' => $this->when($withEconomics, fn () => $this->unit_cost),
            'total_cost' => $this->when($withEconomics, fn () => $this->totalCost()),
        ];
    }
}
