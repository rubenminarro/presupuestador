<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowReceptionChecklistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item' => $this->item,
            'status' => $this->status,
            'notes' => $this->notes,
            'reception_id' => $this->reception_id,
            'reception' => $this->whenLoaded('reception', function () {
                return [
                    'id' => $this->reception->id,
                    'status' => $this->reception->status,
                    'reception_date' => optional($this->reception->reception_date)
                        ->format('Y-m-d'),
                ];
            }),

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('Y-m-d H:i:s'),
        ];
    }
}