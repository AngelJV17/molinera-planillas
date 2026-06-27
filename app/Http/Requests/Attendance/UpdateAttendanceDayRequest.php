<?php
namespace App\Http\Requests\Attendance;

use App\Models\AttendanceDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAttendanceDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepara valores antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'overtime_hours' => $this->input('overtime_hours') ?? 0,
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
            'status_id'      => [
                'required',
                'integer',
                Rule::exists('catalogs', 'id')
                    ->where('type', AttendanceDay::CATALOG_TYPE_STATUS)
                    ->where('status', true),
            ],

            'overtime_hours' => [
                'nullable',
                'numeric',
                'min:0',
                'max:24',
            ],

            'observation'    => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Validación adicional para impedir marcar días futuros.
     *
     * Aunque el frontend bloquee los días futuros, esta validación
     * protege el sistema si alguien intenta enviar la petición manualmente.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var AttendanceDay|null $attendanceDay */
            $attendanceDay = $this->route('attendanceDay');

            if (! $attendanceDay) {
                return;
            }

            if ($attendanceDay->attendance_date->startOfDay()->greaterThan(now()->startOfDay())) {
                $validator->errors()->add(
                    'status_id',
                    'No puedes registrar asistencia de días futuros.'
                );
            }
        });
    }

    /**
     * Nombres amigables para validaciones.
     */
    public function attributes(): array
    {
        return [
            'status_id'      => 'estado del día',
            'overtime_hours' => 'horas extras',
            'observation'    => 'observación',
        ];
    }
}
