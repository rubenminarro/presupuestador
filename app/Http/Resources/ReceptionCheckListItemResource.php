<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionCheckListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'check_list_item_id' => $this->check_list_item_id,
            'name' => $this->checkListItem->name,
            'type' => $this->checkListItem->type,
            'value' => $this->value,
            'observation' => $this->observation,
        ];
    }
}