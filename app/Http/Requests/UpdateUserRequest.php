<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        
        $userId = $this->route('user');

        return [
            'name' => [
                'required', 
                'string', 
                'alpha_dash', 
                'lowercase',
                'min:4',
                'max:20',
                Rule::unique('users', 'name')->ignore($userId),
            ],
            'email' => [
                'required', 
                'email:rfc,dns',
                'max:50',
                'lowercase',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
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
            'role' => [
                'required', 
                'exists:roles,name'
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
            'name.required' => 'El usuario es obligatorio.',
            'name.string' => 'El usuario debe tener el formato correcto.',
            'name.alpha_dash' => 'El usuario solo puede contener letras, números y guiones (sin espacios).',
            'name.lowercase' => 'El usuario debe estar en minúsculas.',
            'name.min' => 'El usuario debe tener al menos 4 caracteres.',
            'name.max' => 'El usuario no debe tener más de 20 caracteres.',
            'name.unique' => 'Este nombre de usuario ya está en uso.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo debe tener el formato correcto.',
            'email.max' => 'El correo no debe tener más de 50 caracteres.',
            'email.lowercase' => 'El correo debe estar en minúsculas.',
            'email.unique' => 'Este correo ya está en uso.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener mínimo de 12 caracteres.',
            'password.mixed' => 'La contraseña debe contener al menos una letra mayúscula y una letra minúscula.',
            'password.numbers' => 'La contraseña debe contener al menos un número.',
            'password.symbols' => 'La contraseña debe contener al menos un símbolo.',       
            'password.letters' => 'La contraseña debe contener al menos una letra.',
            'role.required' => 'El nombre del rol es obligatorio.',
            'role.exists' => 'El rol debe existir.',
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.string' => 'El nombre debe tener el formato correcto.',
            'first_name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'first_name.max' => 'El nombre no debe tener más de 100 caracteres.',
            'first_name.regex' => 'El nombre solo puede contener letras y espacios.',
            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.string' => 'El apellido debe tener el formato correcto.',
            'last_name.min' => 'El apellido debe tener al menos 2 caracteres.',
            'last_name.max' => 'El apellido no debe tener más de 100 caracteres.',
            'last_name.regex' => 'El apellido solo puede contener letras y espacios.',    
        ];
    }
}
