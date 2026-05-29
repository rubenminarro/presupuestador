<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\CheckListType;

class UpdateChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        
        $checkListItemId = $this->route('checkListItem');
    
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
                Rule::enum(CheckListType::class),
            ],
            'required' => [
                'sometimes',
                'boolean',
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
                'unique'   => 'El nombre del checklist ya existe en el sistema.',
            ],
            'type' => [
                'string'   => 'El tipo del checklist debe tener el formato correcto.',
                Enum::class => 'El tipo del checklist seleccionado no es válido.',
            ],
            'required' => [
                'boolean' => 'El campo required debe ser un valor booleano.',
            ],
        ];
    }
}
