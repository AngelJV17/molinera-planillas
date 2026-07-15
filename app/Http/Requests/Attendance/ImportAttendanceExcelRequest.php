<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportAttendanceExcelRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $period = trim(str_replace('/', '-', (string) $this->input('period')));

        if (preg_match('/^\d{2}-\d{4}$/', $period)) {
            [$month, $year] = explode('-', $period);
            $period = "{$year}-{$month}";
        }

        $this->merge(['period' => $period]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'payroll_group_id' => [
                'required',
                'integer',
                Rule::exists('catalogs', 'id')->where(fn ($query) => $query
                    ->where('type', 'PAYROLL_GROUP')
                    ->where('status', true)),
            ],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'period' => 'periodo',
            'payroll_group_id' => 'grupo de planilla',
            'file' => 'archivo Excel',
        ];
    }
}
