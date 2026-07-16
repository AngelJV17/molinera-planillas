<?php

namespace Database\Seeders;

use App\Models\WorkShift;
use Illuminate\Database\Seeder;

class WorkShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workShifts = [
            [
                'name' => 'Turno Día',
                'description' => 'Jornada diurna estándar con descanso al mediodía.',
                'start_time' => '08:00',
                'break_start_time' => '12:00',
                'break_end_time' => '13:00',
                'end_time' => '17:00',
                'tolerance_minutes' => 10,
                'daily_hours' => 8,
                'crosses_midnight' => false,
                'status' => true,
            ],
            [
                'name' => 'Turno Tarde',
                'description' => 'Jornada vespertina con descanso intermedio.',
                'start_time' => '13:00',
                'break_start_time' => '17:00',
                'break_end_time' => '17:30',
                'end_time' => '22:00',
                'tolerance_minutes' => 10,
                'daily_hours' => 8,
                'crosses_midnight' => false,
                'status' => true,
            ],
        ];

        foreach ($workShifts as $shift) {
            WorkShift::firstOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
