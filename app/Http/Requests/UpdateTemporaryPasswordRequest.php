<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateTemporaryPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'current_password:web',
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
                'different:current_password',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
        ];
    }

    /**
     * Nombres amigables para mostrar mensajes de validación en español.
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'contraseña temporal',
            'password' => 'nueva contraseña',
            'password_confirmation' => 'confirmación de contraseña',
        ];
    }

    /**
     * Mensajes personalizados para errores importantes.
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'La contraseña temporal ingresada no es correcta.',
            'password.different'                => 'La nueva contraseña debe ser diferente a la contraseña temporal.',
        ];
    }
}
