<?php
namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => [
                'required',
                'string',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('users', 'username'),
            ],

            'email'    => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'status'   => [
                'required',
                'boolean',
            ],

            'roles'    => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*'  => [
                'string',
                Rule::exists(Role::class, 'name')
                    ->where('guard_name', 'web'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'nombre',
            'username' => 'usuario',
            'email'    => 'correo electrónico',
            'status'   => 'estado',
            'roles'    => 'roles',
            'roles.*'  => 'rol',
        ];
    }
}
