<?php

namespace App\Http\Requests;

use App\Enums\ClientDecisionChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Respuesta del cliente a un repuesto extra (aprobar o rechazar).
 * Al rechazar, el motivo (notes) es obligatorio.
 */
class DecideWorkOrderPartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => [
                'required',
                Rule::enum(ClientDecisionChannel::class),
            ],
            'contact' => [
                'nullable',
                'string',
                'max:150',
            ],
            'notes' => [
                $this->routeIs('work-orders.parts.reject') ? 'required' : 'nullable',
                'string',
                'min:3',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'channel' => [
                'required' => 'El medio por el que respondió el cliente es obligatorio.',
                'enum' => 'El medio seleccionado no es válido.',
            ],
            'contact' => [
                'string' => 'El contacto debe ser un texto válido.',
                'max' => 'El contacto no debe superar los 150 caracteres.',
            ],
            'notes' => [
                'required' => 'El motivo del rechazo es obligatorio.',
                'string' => 'Las notas deben ser un texto válido.',
                'min' => 'Las notas deben tener al menos 3 caracteres.',
                'max' => 'Las notas no deben superar los 500 caracteres.',
            ],
        ];
    }
}
