<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChecklistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        
        $checkListItemId = $this->route('checkListItem')?->id ?? $this->route('checkListItem');
    
        return [
            'name' => [
                'sometimes',
                'string', 
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
                Rule::unique('check_list_items', 'name')->ignore($checkListItemId),
            ],
            'type' => [
                'sometimes',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'string'   => 'El nombre del checklist debe tener el formato correcto.',
                'min'      => 'El nombre del checklist debe tener al menos 2 caracteres.',
                'max'      => 'El nombre del checklist no debe tener más de 100 caracteres.',
                'regex'    => 'El nombre del checklist solo puede contener letras y espacios.',
                'unique'   => 'Este checklist ya existe en el sistema.',
            ],
            'type' => [
                'required' => 'El tipo del checklist es obligatorio.',
                'string'   => 'El tipo del checklist debe tener el formato correcto.',
            ],
        ];
    }
}
