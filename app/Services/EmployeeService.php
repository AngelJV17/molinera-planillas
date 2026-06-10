<?php

namespace App\Services;

use App\Contracts\EmployeeRepositoryInterface;
use App\DTOs\EmployeeStoreDTO;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->employees->paginate($filters);
    }

    public function create(EmployeeStoreDTO $dto): Employee
    {
        return $this->employees->create($dto);
    }

    public function update(Employee $employee, EmployeeStoreDTO $dto): Employee
    {
        return $this->employees->update($employee, $dto);
    }
}
