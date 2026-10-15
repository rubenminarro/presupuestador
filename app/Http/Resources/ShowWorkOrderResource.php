<?php

namespace App\Http\Resources;

use App\Services\WorkOrderClosingService;
use App\Services\WorkOrderCommissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowWorkOrderResource extends JsonResource
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
            'items' => WorkOrderItemResource::collection(
                $this->whenLoaded('items')
            ),
            'commission_summary' => $this->when(
                $this->relationLoaded('items'),
                fn () => app(WorkOrderCommissionService::class)->summary($this->resource)
            ),
            'labor_summary' => $this->when(
                $this->relationLoaded('items'),
                fn () => app(WorkOrderCommissionService::class)->laborSummary($this->resource)
            ),
            'parts' => WorkOrderPartResource::collection(
                $this->whenLoaded('parts')
            ),
            'parts_summary' => $this->whenLoaded('parts', fn () => [
                'total_cost' => $this->parts->reduce(
                    fn (string $sum, $line) => bcadd($sum, $line->totalCost() ?? '0', 2),
                    '0.00'
                ),
                'total_price' => $this->parts->reduce(
                    fn (string $sum, $line) => bcadd($sum, $line->totalPrice(), 2),
                    '0.00'
                ),
                'unresolved_count' => $this->parts->filter(
                    fn ($line) => $line->status->isOpen()
                )->count(),
            ]),
            // Costos y margen: solo con permiso (no visibles para el mecánico).
            'economic_summary' => $this->when(
                $this->relationLoaded('items') &&
                $this->relationLoaded('parts') &&
                (bool) $request->user()?->can('work-orders.view-economics'),
                fn () => app(WorkOrderClosingService::class)->summary($this->resource)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}