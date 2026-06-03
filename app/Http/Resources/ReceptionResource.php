<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\ReceptionStatus;
use App\Enums\VehicleColor;

class ReceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        
        $statusEnum = $this->status instanceof ReceptionStatus 
            ? $this->status 
            : ReceptionStatus::tryFrom($this->status);

        return [
            'id' => $this->id,
            'reception_date' => optional($this->reception_date)->format('d/m/Y'),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'full_name' => trim(
                        $this->client->first_name . ' ' . $this->client->last_name
                    ),
                    'document_number' => $this->client->document_number,
                    'phone' => $this->client->phone,
                    'email' => $this->client->email,
                ];
            }),
            'vehicle' => $this->whenLoaded('vehicle', function () {

                $colorEnum = $this->vehicle->color instanceof VehicleColor 
                    ? $this->vehicle->color 
                    : VehicleColor::tryFrom($this->vehicle->color);

                return [
                    'plate' => $this->vehicle->plate,
                    'color' => [
                        'value' => $colorEnum?->value,
                        'label' => $colorEnum?->label()
                    ],
                ];
            }),
            'problem_description' => $this->problem_description,
            'status' => [
                'value' => $statusEnum?->value,
                'label' => $statusEnum?->label()
            ],
        ];
    }
}
