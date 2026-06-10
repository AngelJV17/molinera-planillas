<?php

namespace App\DTOs;

final readonly class EmployeeStoreDTO
{
    public function __construct(
        public string $documentType,
        public string $documentNumber,
        public string $firstName,
        public string $lastName,
        public string $position,
        public string $area,
        public ?string $hireDate,
        public float $basicSalary,
        public bool $familyAllowance,
        public string $pensionSystem,
        public string $status = 'ACTIVE',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            documentType: $data['document_type'] ?? 'DNI',
            documentNumber: $data['document_number'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            position: $data['position'],
            area: $data['area'],
            hireDate: $data['hire_date'] ?? null,
            basicSalary: (float) $data['basic_salary'],
            familyAllowance: (bool) ($data['family_allowance'] ?? false),
            pensionSystem: $data['pension_system'] ?? 'ONP',
            status: $data['status'] ?? 'ACTIVE',
        );
    }

    public function toArray(): array
    {
        return [
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'position' => $this->position,
            'area' => $this->area,
            'hire_date' => $this->hireDate,
            'basic_salary' => $this->basicSalary,
            'family_allowance' => $this->familyAllowance,
            'pension_system' => $this->pensionSystem,
            'status' => $this->status,
        ];
    }
}
