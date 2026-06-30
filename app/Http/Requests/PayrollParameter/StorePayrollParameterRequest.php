<?php

namespace App\Http\Requests\PayrollParameter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('payroll_parameters', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'value' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'effective_from' => ['nullable', 'date'],
            'status' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'codigo',
            'name' => 'nombre',
            'value' => 'valor',
            'effective_from' => 'fecha de vigencia',
            'status' => 'estado',
            'description' => 'descripcion',
        ];
    }
}
