<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
            ],
            'model' => [
                'id' => $this->brandModel?->id,
                'name' => $this->brandModel?->name,
            ],
            'plate' => $this->plate,
            'no_plate' => (bool) $this->no_plate,
            'chassis' => $this->chassis,
            'color' => $this->color,
            'notes' => $this->notes,
            'active' => (bool) $this->active,
        ];
    }
}