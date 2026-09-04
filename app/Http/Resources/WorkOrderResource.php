<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'reception_id' => $this->reception_id,
            'budget_id' => $this->budget_id,
            'mechanic_id' => $this->mechanic_id,
            'created_by' => $this->created_by,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'notes' => $this->notes,
            'reception' => new ReceptionResource(
                $this->whenLoaded('reception')
            ),
            'budget' => new BudgetResource(
                $this->whenLoaded('budget')
            ),
            'mechanic' => new MechanicResource(
                $this->whenLoaded('mechanic')
            ),
            'creator' => new UserResource(
                $this->whenLoaded('creator')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}