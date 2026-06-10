<?php

namespace App\DTOs;

final readonly class AttendanceImportDTO
{
    public function __construct(
        public int $employeeId,
        public string $attendanceDate,
        public string $status,
        public ?string $checkIn = null,
        public ?string $checkOut = null,
        public float $exchangeableHours = 0,
        public float $overtimeHours = 0,
        public ?string $observations = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: (int) $data['employee_id'],
            attendanceDate: $data['attendance_date'],
            status: $data['status'],
            checkIn: $data['check_in'] ?? null,
            checkOut: $data['check_out'] ?? null,
            exchangeableHours: (float) ($data['exchangeable_hours'] ?? 0),
            overtimeHours: (float) ($data['overtime_hours'] ?? 0),
            observations: $data['observations'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'attendance_date' => $this->attendanceDate,
            'status' => $this->status,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'exchangeable_hours' => $this->exchangeableHours,
            'overtime_hours' => $this->overtimeHours,
            'observations' => $this->observations,
        ];
    }
}
