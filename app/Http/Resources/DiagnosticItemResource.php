<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosticItemResource extends JsonResource
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
            'diagnostic_id' => $this->diagnostic_id,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity,
            'status' => $this->status,
            'requires_repair' => $this->requires_repair,
            'requires_replacement' => $this->requires_replacement,
            'estimated_cost' => $this->estimated_cost,
            'estimated_time' => $this->estimated_time,
            'recommendation' => $this->recommendation,
            'diagnostic' => new DiagnosticResource(
                $this->whenLoaded('diagnostic')
            ),  
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
