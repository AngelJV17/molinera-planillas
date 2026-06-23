<?php

namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determina si el usuario puede ejecutar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar un trabajador.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:20', Rule::unique('employees', 'employee_code')->withoutTrashed()],
            'document_type_id' => ['required', 'exists:catalogs,id'],
            'document_number' => ['required', 'string', 'max:20', Rule::unique('employees', 'document_number')->withoutTrashed()],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender_id' => ['nullable', 'exists:catalogs,id'],
            'marital_status_id' => ['nullable', 'exists:catalogs,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'position_id' => ['nullable', 'exists:catalogs,id'],
            'work_area_id' => ['nullable', 'exists:catalogs,id'],
            'work_shift_id' => ['nullable', 'exists:work_shifts,id'],
            'employment_status_id' => ['nullable', 'exists:catalogs,id'],
            'base_salary' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'pension_system_id' => ['nullable', 'exists:catalogs,id'],
            'cuspp' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_code.required' => 'El código del trabajador es obligatorio.',
            'employee_code.unique' => 'Ya existe un trabajador con este código.',
            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique' => 'Ya existe un trabajador con este documento.',
            'first_name.required' => 'Los nombres son obligatorios.',
            'last_name.required' => 'Los apellidos son obligatorios.',
            'hire_date.required' => 'La fecha de ingreso es obligatoria.',
            'base_salary.required' => 'El sueldo básico es obligatorio.',
        ];
    }
}
