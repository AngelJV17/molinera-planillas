<?php

namespace App\Http\Requests\WorkShift;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkShiftRequest extends FormRequest
{
    /**
     * Determina si el usuario puede ejecutar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar un turno laboral.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('work_shifts', 'name')
                    ->withoutTrashed()
                    ->ignore($this->route('work_shift')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],

            'break_start_time'  => ['nullable', 'date_format:H:i', 'required_with:break_end_time'],
            'break_end_time'    => ['nullable', 'date_format:H:i', 'required_with:break_start_time'],
            'tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'daily_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'crosses_midnight' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del turno es obligatorio.',
            'name.unique' => 'Ya existe un turno con este nombre.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:MM.',
            'break_start_time.date_format' => 'La hora de inicio del descanso debe tener el formato HH:MM.',
            'break_end_time.date_format' => 'La hora de fin del descanso debe tener el formato HH:MM.',
            'tolerance_minutes.required' => 'Los minutos de tolerancia son obligatorios.',
            'daily_hours.required' => 'Las horas de jornada son obligatorias.',
        ];
    }
}
