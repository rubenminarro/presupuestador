<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionCheckListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reception_id' => $this->reception_id,
            'status' => $this->status,
            'items' => ReceptionCheckListItemResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}