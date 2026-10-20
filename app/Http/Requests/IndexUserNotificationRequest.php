<?php

namespace App\Http\Requests;

use App\Enums\InternalNotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class IndexUserNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unread' => ['nullable', 'boolean'],
            'type' => ['nullable', Rule::enum(InternalNotificationType::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'unread' => [
                'boolean' => 'El filtro de no leídas debe ser verdadero o falso.',
            ],
            'type' => [
                Enum::class => 'El tipo debe ser ' . implode(', ', InternalNotificationType::values()) . '.',
            ],
            'per_page' => [
                'integer' => 'La cantidad por página debe ser un número entero.',
                'min' => 'La cantidad por página debe ser al menos 1.',
                'max' => 'La cantidad por página no puede superar 100.',
            ],
        ];
    }
}
