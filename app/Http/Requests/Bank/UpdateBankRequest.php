<?php

namespace App\Http\Requests\Bank;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankRequest extends FormRequest
{
    /**
     * Determina si el usuario puede ejecutar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar un banco.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('banks', 'name')
                    ->withoutTrashed()
                    ->ignore($this->route('bank')),
            ],

            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('banks', 'code')
                    ->withoutTrashed()
                    ->ignore($this->route('bank')),
            ],

            'status' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe un banco con este nombre.',
            'code.unique' => 'Ya existe un banco con este código.',
            'status.required' => 'El estado es obligatorio.',
        ];
    }
}
