<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosticResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reception_id' => $this->reception_id,
            'mechanic_id' => $this->mechanic_id,
            'customer_complaint' => $this->customer_complaint,
            'diagnosis' => $this->diagnosis,
            'recommendation' => $this->recommendation,
            'priority' => $this->priority,
            'status' => $this->status,
            'requires_parts' => $this->requires_parts,
            'requires_repair' => $this->requires_repair,
            'diagnosed_at' => $this->diagnosed_at,
            'reception' => new ShowReceptionResource(
                $this->whenLoaded('reception')
            ),
            'mechanic' => new UserResource(
                $this->whenLoaded('mechanic')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
