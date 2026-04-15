<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => strtolower($this->name),
            'description' => $this->description,
            'guard_name' => $this->guard_name,
            'active'=> $this->active,
            'permissions' => $this->permissions->pluck('id')
        ];
    }
}
