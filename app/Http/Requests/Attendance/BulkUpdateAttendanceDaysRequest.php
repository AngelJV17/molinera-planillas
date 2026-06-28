<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateAttendanceDaysRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepara datos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'day_ids' => collect($this->input('day_ids', []))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray(),

            'status_code' => strtoupper((string) $this->input('status_code')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'day_ids' => [
                'required',
                'array',
                'min:1',
                'max:31',
            ],

            'day_ids.*' => [
                'required',
                'integer',
                'exists:attendance_days,id',
            ],

            'status_code' => [
                'required',
                'string',
                Rule::in([
                    AttendanceDay::STATUS_PRESENT,
                    AttendanceDay::STATUS_ABSENT,
                    AttendanceDay::STATUS_REST,
                ]),
            ],
        ];
    }
}
