<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reception_id' => $this->reception_id,
            'original_name' => $this->original_name,
            'description' => $this->description,
            'url' => $this->path ? asset('storage/' . $this->path) : null,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}