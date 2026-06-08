<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\FuelLevel;

class ShowReceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        
        $fuelEnum = $this->fuel_level instanceof FuelLevel 
            ? $this->fuel_level 
            : FuelLevel::tryFrom($this->fuel_level);
    
        return [
            'id' => $this->id,
            'reception_date' => optional($this->reception_date)->format('d/m/Y'),
            'estimated_delivery_date' => optional($this->estimated_delivery_date)->format('d/m/Y'),
            'observations' => $this->observations,
            'mileage' => 'Km: ' . $this->mileage,
            'fuel' => [
                'value' => $fuelEnum?->value,
                'label' => $fuelEnum?->label()
            ],
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => optional($this->createdBy)->id,
                    'name' => optional($this->createdBy)->name,
                ];
            }),
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => optional($this->approvedBy)->id,
                    'name' => optional($this->approvedBy)->name,
                ];
            }),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'full_name' => trim(
                        $this->client->first_name . ' ' . $this->client->last_name
                    ),
                    'document_number' => $this->client->document_number,
                    'phone' => $this->client->phone,
                    'email' => $this->client->email,
                ];
            }),
            'vehicle' => $this->whenLoaded('vehicle', function () {
                return [
                    'id' => $this->vehicle->id,
                    'plate' => $this->vehicle->plate,
                    'year' => $this->vehicle->year,
                    'color' => $this->vehicle->color,
                    'vin' => $this->vehicle->vin,
                    'engine_number' => $this->vehicle->engine_number,
                ];
            }),
            'service_categories' => $this->whenLoaded('serviceCategories', function () {
                return $this->serviceCategories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                    ];
                });
            }),
        ];
    }
}