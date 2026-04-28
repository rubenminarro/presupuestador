<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowReceptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            
            'id' => $this->id,
            'reception_date' => optional($this->reception_date)->format('Y-m-d'),
            'estimated_delivery_date' => optional($this->estimated_delivery_date)->format('Y-m-d'),
            'mileage' => $this->mileage,
            'fuel_level' => $this->fuel_level,
            'problem_description' => $this->problem_description,
            'observations' => $this->observations,
            'status' => $this->status,
            'active' => $this->active,
            
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

            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}