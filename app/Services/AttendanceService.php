<?php

namespace App\Services;

use App\Contracts\AttendanceRepositoryInterface;
use App\DTOs\AttendanceImportDTO;
use App\Models\Attendance;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendances,
    ) {
    }

    public function saveDailyDetail(AttendanceImportDTO $dto): Attendance
    {
        return $this->attendances->upsertDailyRecord($dto);
    }
}
