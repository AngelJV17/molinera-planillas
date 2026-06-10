<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('payrolls')->where(fn ($query) => $query
                    ->where('period_year', $this->integer('period_year'))
                    ->where('period_month', $this->integer('period_month'))),
            ],
        ];
    }
}
