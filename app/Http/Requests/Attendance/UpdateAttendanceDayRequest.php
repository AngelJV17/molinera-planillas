<?php
namespace App\Http\Requests\Attendance;

use App\Models\AttendanceDay;
use App\Models\Catalog;
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
            'entry_time'  => $this->filled('entry_time')
                ? $this->input('entry_time')
                : null,

            'exit_time'   => $this->filled('exit_time')
                ? $this->input('exit_time')
                : null,

            'observation' => $this->filled('observation')
                ? trim((string) $this->input('observation'))
                : null,
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
            'status_id'   => [
                'required',
                'integer',
                Rule::exists('catalogs', 'id')
                    ->where('type', AttendanceDay::CATALOG_TYPE_STATUS)
                    ->where('status', true),
            ],

            'entry_time'  => [
                'nullable',
                'date_format:H:i',
            ],

            'exit_time'   => [
                'nullable',
                'date_format:H:i',
            ],

            'observation' => [
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

                return;
            }

            $status = Catalog::query()
                ->where('id', $this->input('status_id'))
                ->where('type', AttendanceDay::CATALOG_TYPE_STATUS)
                ->where('status', true)
                ->first();

            if (!$status) {
                return;
            }

            $requiresWorkingHours = in_array($status->code, [
                AttendanceDay::STATUS_PRESENT,
                AttendanceDay::STATUS_EXCHANGE_WORKED,
            ], true);

            if (! $requiresWorkingHours) {
                return;
            }

            if (! $this->filled('entry_time')) {
                $validator->errors()->add(
                    'entry_time',
                    'La hora de ingreso es obligatoria cuando el día se marca como asistido o trabajado como canje.'
                );
            }

            if (! $this->filled('exit_time')) {
                $validator->errors()->add(
                    'exit_time',
                    'La hora de salida es obligatoria cuando el día se marca como asistido o trabajado como canje.'
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
            'status_id' => 'estado del día',
            'entry_time' => 'hora de ingreso',
            'exit_time' => 'hora de salida',
            'observation' => 'observación',
        ];
    }
}
