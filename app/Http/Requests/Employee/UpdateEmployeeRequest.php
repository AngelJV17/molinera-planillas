<?php
namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determina si el usuario puede ejecutar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar un trabajador.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_code'        => [
                'required', 'string', 'max:20',
                Rule::unique('employees', 'employee_code')
                    ->withoutTrashed()->ignore($this->route('worker')),
            ],

            'document_type_id'     => [
                'required',
                Rule::exists('catalogs', 'id')->where('type', 'DOCUMENT_TYPE'),
            ],

            'document_number'      => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'document_number')
                    ->withoutTrashed()
                    ->ignore($this->route('worker')),
            ],

            'first_name'           => ['required', 'string', 'max:100'],
            'last_name'            => ['required', 'string', 'max:100'],
            'birth_date'           => ['nullable', 'date', 'before:today'],

            'gender_id'            => [
                'nullable',
                Rule::exists('catalogs', 'id')->where('type', 'GENDER'),
            ],

            'marital_status_id'    => [
                'nullable',
                Rule::exists('catalogs', 'id')->where('type', 'MARITAL_STATUS'),
            ],

            'phone'                => ['nullable', 'string', 'max:20'],
            'email'                => ['nullable', 'email', 'max:255'],
            'address'              => ['nullable', 'string', 'max:255'],
            'district_id'          => ['nullable', 'exists:districts,id'],
            'hire_date'            => ['required', 'date'],
            'termination_date'     => ['nullable', 'date', 'after_or_equal:hire_date'],

            'position_id'          => [
                'nullable',
                Rule::exists('catalogs', 'id')->where('type', 'POSITION'),
            ],

            'work_area_id'         => [
                'nullable',
                Rule::exists('catalogs', 'id')->where('type', 'WORK_AREA'),
            ],

            'work_shift_id'        => ['nullable', 'exists:work_shifts,id'],

            'employment_status_id' => [
                'nullable',
                Rule::exists('catalogs', 'id')->where('type', 'WORKER_STATUS'),
            ],

            'base_salary'          => ['required', 'numeric', 'min:0', 'max:99999999.99'],

            'pension_system_id'    => [
                'nullable',
                Rule::exists('catalogs', 'id')->where('type', 'PENSION_SYSTEM'),
            ],

            'cuspp'                => ['nullable', 'string', 'max:50'],
            'status'               => ['required', 'boolean'],
            'has_system_access' => ['required', 'boolean'],
            'bank_accounts' => ['nullable', 'array', 'max:5'],
            'bank_accounts.*.bank_id' => ['required_with:bank_accounts.*.account_number', 'nullable', 'exists:banks,id'],
            'bank_accounts.*.account_type_id' => ['required_with:bank_accounts.*.account_number', 'nullable', Rule::exists('catalogs', 'id')->where('type', 'ACCOUNT_TYPE')],
            'bank_accounts.*.account_number' => ['required_with:bank_accounts.*.bank_id', 'nullable', 'string', 'max:50'],
            'bank_accounts.*.cci' => ['nullable', 'string', 'max:50'],
            'bank_accounts.*.is_primary' => ['nullable', 'boolean'],
            'bank_accounts.*.status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_code.required'    => 'El código del trabajador es obligatorio.',
            'employee_code.unique'      => 'Ya existe un trabajador con este código.',
            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_number.required'  => 'El número de documento es obligatorio.',
            'document_number.unique'    => 'Ya existe un trabajador con este documento.',
            'first_name.required'       => 'Los nombres son obligatorios.',
            'last_name.required'        => 'Los apellidos son obligatorios.',
            'hire_date.required'        => 'La fecha de ingreso es obligatoria.',
            'base_salary.required'      => 'El sueldo básico es obligatorio.',
        ];
    }
}
