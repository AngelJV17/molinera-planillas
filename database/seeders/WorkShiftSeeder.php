<?php

namespace Database\Seeders;

use App\Models\WorkShift;
use Illuminate\Database\Seeder;

class WorkShiftSeeder extends Seeder
{
    public function run(): void
    {
        $morning = WorkShift::updateOrCreate(
            ['name' => 'Turno Mañana'],
            [
                'description' => 'Jornada diurna regular. Sabado hasta mediodia y cuenta como dia completo.',
                'start_time' => '08:00',
                'break_start_time' => '12:00',
                'break_end_time' => '13:00',
                'end_time' => '17:00',
                'tolerance_minutes' => 10,
                'daily_hours' => 8,
                'uses_daily_rules' => true,
                'crosses_midnight' => false,
                'rotation_enabled' => false,
                'rotation_work_days' => null,
                'rotation_rest_days' => null,
                'rotation_start_date' => null,
                'status' => true,
            ]
        );

        $morningRules = [
            1 => ['is_working_day' => true, 'start_time' => '08:00', 'break_start_time' => '12:00', 'break_end_time' => '13:00', 'end_time' => '17:00', 'expected_hours' => 8, 'overtime_pay_enabled' => true, 'overtime_after_hours' => 8],
            2 => ['is_working_day' => true, 'start_time' => '08:00', 'break_start_time' => '12:00', 'break_end_time' => '13:00', 'end_time' => '17:00', 'expected_hours' => 8, 'overtime_pay_enabled' => true, 'overtime_after_hours' => 8],
            3 => ['is_working_day' => true, 'start_time' => '08:00', 'break_start_time' => '12:00', 'break_end_time' => '13:00', 'end_time' => '17:00', 'expected_hours' => 8, 'overtime_pay_enabled' => true, 'overtime_after_hours' => 8],
            4 => ['is_working_day' => true, 'start_time' => '08:00', 'break_start_time' => '12:00', 'break_end_time' => '13:00', 'end_time' => '17:00', 'expected_hours' => 8, 'overtime_pay_enabled' => true, 'overtime_after_hours' => 8],
            5 => ['is_working_day' => true, 'start_time' => '08:00', 'break_start_time' => '12:00', 'break_end_time' => '13:00', 'end_time' => '17:00', 'expected_hours' => 8, 'overtime_pay_enabled' => true, 'overtime_after_hours' => 8],
            6 => ['is_working_day' => true, 'start_time' => '08:00', 'break_start_time' => null, 'break_end_time' => null, 'end_time' => '12:00', 'expected_hours' => 4, 'overtime_pay_enabled' => false, 'overtime_after_hours' => null],
            7 => ['is_working_day' => false, 'start_time' => null, 'break_start_time' => null, 'break_end_time' => null, 'end_time' => null, 'expected_hours' => 0, 'overtime_pay_enabled' => false, 'overtime_after_hours' => null],
        ];

        foreach ($morningRules as $day => $rule) {
            $morning->rules()->updateOrCreate(
                ['day_of_week' => $day],
                array_merge($rule, [
                    'tolerance_minutes' => 10,
                    'crosses_midnight' => false,
                    'counts_as_full_day' => true,
                ])
            );
        }

        $night = WorkShift::updateOrCreate(
            ['name' => 'Turno Noche Rotativo 6x1'],
            [
                'description' => 'Servicio de vigilancia nocturna. Trabaja 6 noches y descansa 1.',
                'start_time' => '18:00',
                'break_start_time' => null,
                'break_end_time' => null,
                'end_time' => '06:00',
                'tolerance_minutes' => 10,
                'daily_hours' => 12,
                'uses_daily_rules' => false,
                'crosses_midnight' => true,
                'rotation_enabled' => true,
                'rotation_work_days' => 6,
                'rotation_rest_days' => 1,
                'rotation_start_date' => '2026-07-01',
                'status' => true,
            ]
        );

        $night->rules()->delete();
    }
}
