<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ReceptionCheckListItem;
use Illuminate\Validation\Rules\Enum;
use App\Enums\CheckListValue;

class UpdateReceptionCheckListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        
        $rules = [
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.id' => [
                'required', 
                Rule::exists('reception_check_list_items', 'id')
            ],
            'items.*.observation' => [
                'nullable', 
                'string', 
                'max:500', 
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
        ];
        
        $typeRules = [
            'boolean' => 'boolean',
            'number'  => 'numeric',
            'text'    => 'string',
        ];

        $pivotIds = collect($this->input('items'))->pluck('id')->filter()->toArray();

        if (!empty($pivotIds)) {
            
            $receptionItems = ReceptionCheckListItem::with('checkListItem')
                ->whereIn('id', $pivotIds)
                ->get()
                ->keyBy('id');

            foreach ($this->input('items', []) as $index => $item) {
                $pivotId = $item['id'] ?? null;
                $receptionItem = $receptionItems->get($pivotId);

                $dbType = $receptionItem?->checkListItem?->type;
                
                if ($dbType) {
                    $valueRules = ['nullable'];

                    switch ($dbType) {
                        case 'boolean':
                            $valueRules[] = 'boolean';
                            break;
                        case 'number':
                            $valueRules[] = 'numeric';
                            break;
                        case 'text':
                            $valueRules[] = Rule::enum(CheckListValue::class);
                            break;
                    }

                    $rules["items.{$index}.value"] = $valueRules;
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {

        return [
            'item' => [
                'required' => 'Debe indicar el ítem inspeccionado.',
                'array' => 'El ítem debe ser un arreglo de objetos.',
                'min' => 'El ítem debe tener al menos :min caracteres.',
            ],
            'items.*.id' => [
                'required' => 'Debe indicar el ID del ítem de checklist.',
                'exists' => 'El ID del ítem de checklist no es válido.',
            ],
            'items.*.value' => [
                Enum::class => 'El tipo del checklist seleccionado no es válido.',
                'boolean' => 'El valor debe ser verdadero o falso.',
                'numeric' => 'El valor debe ser un número válido.',
                'string' => 'El valor debe ser una cadena de texto.',
            ],
            'items.*.observation' => [
                'string' => 'La observación del ítem debe ser una cadena de texto.',
                'max' => 'La observación del ítem no debe exceder los :max caracteres.',
                'regex' => 'La observación del ítem contiene caracteres no permitidos.',
            ],
        ];
    }
}
