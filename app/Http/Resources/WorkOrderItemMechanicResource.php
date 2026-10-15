<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * La comisión se calcula sobre workOrderItem.budgetItem.total; al cargarse
 * desde WorkOrderItem::mechanicAssignments() el ítem ya viene hidratado.
 */
class WorkOrderItemMechanicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itemTotal = (string) ($this->workOrderItem?->budgetItem?->total ?? '0');

        return [
            'id' => $this->id,
            'work_order_item_id' => $this->work_order_item_id,
            'mechanic_id' => $this->mechanic_id,
            'mechanic' => $this->whenLoaded('mechanic', fn () => [
                'id' => $this->mechanic->id,
                'employee_code' => $this->mechanic->employee_code,
                'full_name' => $this->mechanic->fullName(),
            ]),
            'share_percentage' => $this->share_percentage,
            'commission_percentage' => $this->commission_percentage,
            'commission_amount' => $this->commissionAmount($itemTotal),
            'hours' => $this->hours,
            'hour_cost' => $this->hour_cost,
            'labor_cost' => $this->laborCost(),
        ];
    }
}
