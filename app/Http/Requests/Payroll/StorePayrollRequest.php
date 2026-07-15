<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'payroll_group_id' => ['required', 'integer', Rule::exists('catalogs', 'id')->where('type', 'PAYROLL_GROUP')->where('status', true)],
            'payment_date' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'month' => 'mes',
            'year' => 'anio',
            'payroll_group_id' => 'grupo de planilla',
            'payment_date' => 'fecha de pago',
            'observations' => 'observaciones',
        ];
    }
}
