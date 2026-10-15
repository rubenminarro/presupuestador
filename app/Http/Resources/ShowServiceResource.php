<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'service_category_id' => $this->service_category_id,
            'standard_price' => $this->standard_price,
            'estimated_hours' => $this->estimated_hours,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'category' => new ServiceCategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
