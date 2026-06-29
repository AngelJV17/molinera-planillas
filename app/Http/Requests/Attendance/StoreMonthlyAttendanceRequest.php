<?php
namespace App\Http\Requests\Attendance;

use App\Models\Employee;
use App\Models\MonthlyAttendance;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMonthlyAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id'  => [
                'required',
                'integer',
                'exists:employees,id',
            ],

            'month'        => [
                'required',
                'integer',
                'between:1,12',
            ],

            'year'         => [
                'required',
                'integer',
                'between:2000,2100',
            ],

            'observations' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Validación adicional para evitar duplicados antes de llegar al servicio.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $exists = MonthlyAttendance::query()
                ->where('employee_id', $this->input('employee_id'))
                ->where('month', $this->input('month'))
                ->where('year', $this->input('year'))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'employee_id',
                    'Este trabajador ya tiene una asistencia mensual registrada para el mes y año seleccionados.'
                );
            }

            $employee = Employee::query()
                ->select(['id', 'status', 'work_shift_id'])
                ->find($this->input('employee_id'));

            if ($employee && ! $employee->status) {
                $validator->errors()->add(
                    'employee_id',
                    'Solo puedes registrar asistencia para trabajadores activos.'
                );
            }

            if ($employee && ! $employee->work_shift_id) {
                $validator->errors()->add(
                    'employee_id',
                    'Solo puedes registrar asistencia para trabajadores con turno asignado.'
                );
            }

            $selectedPeriod = Carbon::create(
                (int) $this->input('year'),
                (int) $this->input('month'),
                1
            )->startOfMonth();

            $currentPeriod  = now()->startOfMonth();
            $previousPeriod = now()->subMonthNoOverflow()->startOfMonth();

            $isAllowedPeriod = $selectedPeriod->equalTo($currentPeriod)
            || $selectedPeriod->equalTo($previousPeriod);

            if (! $isAllowedPeriod) {
                $validator->errors()->add(
                    'period',
                    'Solo puedes registrar asistencia del mes actual o del mes anterior.'
                );

                $validator->errors()->add(
                    'month',
                    'Solo puedes registrar asistencia del mes actual o del mes anterior.'
                );
            }
        });
    }

    /**
     * Nombres amigables para los mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'employee_id'  => 'trabajador',
            'month'        => 'mes',
            'year'         => 'año',
            'observations' => 'observaciones',
        ];
    }
}
