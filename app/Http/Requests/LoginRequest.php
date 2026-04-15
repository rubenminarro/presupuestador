<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:50'],
            'password' => ['required']
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El campo del Correo es obligatorio.',
            'email.email' => 'El campo del Correo debe tener el formato correcto.',
            'email.max' => 'El campo del Correo no debe tener más de 50 caracteres.',
            'password.required' => 'El campo de la contraseña es obligatorio.',
        ];
    }
}
