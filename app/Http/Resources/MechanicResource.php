<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\MechanicStatus;

class MechanicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        
        $statusEnum = $this->status instanceof MechanicStatus 
            ? $this->status 
            : MechanicStatus::tryFrom($this->status);
    
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'name' => $this->user->name,
                    'full_name' => trim(
                        $this->user->first_name . ' ' . $this->user->last_name
                    ),
                    'email' => $this->user->email,
                ];
            }),
            'specialty' => $this->specialty,
            'hire_date' => optional($this->hire_date)->format('Y-m-d'),
            'hour_cost' => $this->hour_cost,
            'commission_percentage' => $this->commission_percentage,
            'status' => [
                'value' => $statusEnum?->value,
                'label' => $statusEnum?->label()
            ],
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
