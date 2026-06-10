<?php

namespace App\Contracts;

use App\DTOs\AttendanceImportDTO;
use App\Models\Attendance;
use Illuminate\Support\Collection;

interface AttendanceRepositoryInterface
{
    public function upsertDailyRecord(AttendanceImportDTO $dto): Attendance;

    public function forPeriod(int $year, int $month): Collection;

    public function employeeOvertimeForPeriod(int $employeeId, int $year, int $month): float;
}