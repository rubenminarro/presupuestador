<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tax_id' => $this->tax_id,
            'contact_name' => $this->contact_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'parts_count' => $this->whenCounted('parts'),
            'pivot' => $this->whenPivotLoaded('part_supplier', fn () => [
                'supplier_part_code' => $this->pivot->supplier_part_code,
                'last_cost' => $this->pivot->last_cost,
                'is_preferred' => (bool) $this->pivot->is_preferred,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
