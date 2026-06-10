<?php

namespace App\Repositories;

use App\Contracts\AttendanceRepositoryInterface;
use App\DTOs\AttendanceImportDTO;
use App\Models\Attendance;
use Illuminate\Support\Collection;

class EloquentAttendanceRepository implements AttendanceRepositoryInterface
{
    public function upsertDailyRecord(AttendanceImportDTO $dto): Attendance
    {
        return Attendance::updateOrCreate(
            [
                'employee_id' => $dto->employeeId,
                'attendance_date' => $dto->attendanceDate,
            ],
            $dto->toArray(),
        );
    }

    public function forPeriod(int $year, int $month): Collection
    {
        return Attendance::query()
            ->with('employee')
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();
    }

    public function employeeOvertimeForPeriod(int $employeeId, int $year, int $month): float
    {
        return (float) Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->sum('overtime_hours');
    }
}
