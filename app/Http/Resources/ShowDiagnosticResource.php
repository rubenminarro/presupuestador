<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowDiagnosticResource extends JsonResource
{
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
            'diagnosed_at' => optional($this->diagnosed_at)->format('d/m/Y H:i'),
            'reception' => new ShowReceptionResource(
                $this->whenLoaded('reception')
            ),
            'mechanic' => $this->whenLoaded('mechanic', function () {
                return [
                    'id' => $this->mechanic->id,
                    'name' => $this->mechanic->name,
                    'email' => $this->mechanic->email,
                ];
            }),
            'items' => DiagnosticItemResource::collection(
                $this->whenLoaded('items')
            ),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
