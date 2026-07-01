<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosticResource extends JsonResource
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
            'requires_parts' => $this->requires_parts,
            'requires_repair' => $this->requires_repair,
            'diagnosed_at' => $this->diagnosed_at,
            'reception' => $this->whenLoaded('reception', function () {
                return [
                    'id' => $this->reception->id,
                    'reception_date' => $this->reception->reception_date->format('d/m/Y H:i'),
                ];
            }),
            'mechanic' => $this->whenLoaded('mechanic', function () {
                return [
                    'id' => $this->mechanic->id,
                    'employee_code' => $this->mechanic->employee_code,
                    'specialty' => $this->mechanic->specialty,
                    'status' => $this->mechanic->status,

                    /*'user' => $this->whenLoaded('user', function () {
                        return [
                            'id' => $this->mechanic->user->id,
                            'name' => $this->mechanic->user->name,
                            'first_name' => $this->mechanic->user->first_name,
                            'last_name' => $this->mechanic->user->last_name,
                            'email' => $this->mechanic->user->email,
                        ];
                    }),*/
                ];
            }),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
