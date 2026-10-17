<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Guaraníes: sin decimales.
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            // Solo efectivo: lo entregado por el cliente (para calcular el vuelto).
            'amount_received' => [
                Rule::prohibitedIf(fn () => $this->input('method') !== 'cash'),
                'nullable',
                'integer',
                'gte:amount',
            ],
            'payment_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reference' => [
                Rule::requiredIf(fn () => in_array($this->input('method'), ['card', 'transfer', 'check'], true)),
                'nullable',
                'string',
                'max:100',
                'regex:/^[\pL\pN\s.\-\/#]*$/u',
            ],
            'bank' => [
                Rule::requiredIf(fn () => $this->input('method') === 'check'),
                'nullable',
                'string',
                'max:100',
                'regex:/^[\pL\pN\s.,\-&()]*$/u',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount' => [
                'required' => 'El monto es obligatorio.',
                'integer' => 'El monto debe ser un número entero (guaraníes, sin decimales).',
                'min' => 'El monto debe ser mayor a cero.',
            ],
            'method' => [
                'required' => 'El medio de pago es obligatorio.',
                Enum::class => 'El medio de pago debe ser cash, card, transfer o check.',
            ],
            'amount_received' => [
                'prohibited' => 'El monto entregado solo se informa en pagos en efectivo.',
                'integer' => 'El monto entregado debe ser un número entero (guaraníes, sin decimales).',
                'gte' => 'El monto entregado no puede ser menor al monto del pago.',
            ],
            'payment_date' => [
                'date_format' => 'La fecha del pago debe tener el formato AAAA-MM-DD.',
                'before_or_equal' => 'La fecha del pago no puede ser futura.',
            ],
            'reference' => [
                'required' => 'La referencia (voucher, operación o número de cheque) es obligatoria para este medio de pago.',
                'string' => 'La referencia debe ser un texto válido.',
                'max' => 'La referencia no debe superar los 100 caracteres.',
                'regex' => 'La referencia contiene caracteres no permitidos.',
            ],
            'bank' => [
                'required' => 'El banco es obligatorio para pagos con cheque.',
                'string' => 'El banco debe ser un texto válido.',
                'max' => 'El banco no debe superar los 100 caracteres.',
                'regex' => 'El banco contiene caracteres no permitidos.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser un texto válido.',
                'max' => 'Las notas no deben superar los 1000 caracteres.',
            ],
        ];
    }
}
