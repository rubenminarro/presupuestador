<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Edición parcial. Que quede al menos un intervalo lo controla el Service.
 */
class UpdateMaintenancePlanRequest extends StoreMaintenancePlanRequest
{
    public function rules(): array
    {
        $planId = $this->route('maintenancePlan')?->id;

        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:100', 'regex:/^[\pL\pN\s.,()\-\/]+$/u', Rule::unique('maintenance_plans', 'name')->ignore($planId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000', 'regex:/^[\pL\pN\s.,;:()\-#@!?%\/]*$/u'],
            'service_id' => ['sometimes', 'nullable', 'integer', Rule::exists('services', 'id')->withoutTrashed()],
            'interval_km' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'interval_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
