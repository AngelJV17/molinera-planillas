<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'document_type' => ['required', 'string', 'max:20'],
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'document_number')->ignore($employeeId),
            ],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:120'],
            'area' => ['required', 'string', 'max:120'],
            'hire_date' => ['nullable', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'family_allowance' => ['required', 'boolean'],
            'pension_system' => ['required', 'string', 'max:20'],
            'status' => ['required', 'string', 'max:30'],
        ];
    }
}
