<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name' => [
                'required', 
                'string', 
                'alpha_dash', 
                'lowercase',
                'min:4',
                'max:20',
                Rule::unique('users', 'name'),
            ],
            'email' => [
                'required', 
                'email:rfc,dns',
                'max:50',
                'lowercase',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required', 
                'nullable', 
                'confirmed',
                Password::min(5)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                ,
            ],
            'password_confirmation' => [
                'required:password',
                'same:password'
            ],
            'role' => [
                'required',
                Rule::exists('roles', 'name')
            ],
            'first_name' => [
                'required',
                'string', 
                'min:2', 
                'max:100', 
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u'
            ],
            'last_name' => [
                'required',
                'string', 
                'min:2', 
                'max:100', 
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El usuario es obligatorio.',
                'string' => 'El usuario debe tener el formato correcto.',
                'alpha_dash' => 'El usuario solo puede contener letras, números y guiones (sin espacios).',
                'lowercase' => 'El usuario debe estar en minúsculas.',
                'min' => 'El usuario debe tener al menos 4 caracteres.',
                'max' => 'El usuario no debe tener más de 20 caracteres.',
                'unique' => 'Este nombre de usuario ya está en uso.',
            ],
            'email' => [
                'required' => 'El correo es obligatorio.',
                'email' => 'El correo debe tener el formato correcto.',
                'max' => 'El correo no debe tener más de 50 caracteres.',
                'lowercase' => 'El correo debe estar en minúsculas.',
                'unique' => 'Este correo ya está en uso.',
            ],
            'password' => [
                'required' => 'La contraseña es obligatoria.',
                'confirmed' => 'Las contraseñas no coinciden.',
                'min' => 'La contraseña debe tener mínimo de 12 caracteres.',
                'mixed' => 'La contraseña debe contener al menos una letra mayúscula y una letra minúscula.',
                'numbers' => 'La contraseña debe contener al menos un número.',
                'symbols' => 'La contraseña debe contener al menos un símbolo.',       
                'letters' => 'La contraseña debe contener al menos una letra.',
                'uncompromised' => 'La contraseña ha sido expuesta en una filtración de datos. Por favor, elige una contraseña diferente.',
            ],
            'password_confirmation' => [
                'required' => 'La confirmación de la contraseña es obligatoria.',
                'same' => 'La confirmación de la contraseña debe coincidir con la contraseña.',
            ],
            'role' => [
                'required' => 'El nombre del rol es obligatorio.',
                'exists' => 'El rol debe existir.',
            ],
            'first_name' => [
                'required' => 'El nombre es obligatorio.',
                'string' => 'El nombre debe tener el formato correcto.',
                'min' => 'El nombre debe tener al menos 2 caracteres.',
                'max' => 'El nombre no debe tener más de 100 caracteres.',
                'regex' => 'El nombre solo puede contener letras y espacios.',
            ],
            'last_name' => [
                'required' => 'El apellido es obligatorio.',
                'string' => 'El apellido debe tener el formato correcto.',
                'min' => 'El apellido debe tener al menos 2 caracteres.',
                'max' => 'El apellido no debe tener más de 100 caracteres.',
                'regex' => 'El apellido solo puede contener letras y espacios.',
            ],
        ];
    }
}
