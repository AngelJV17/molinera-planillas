<?php

namespace App\Contracts;

use App\DTOs\EmployeeStoreDTO;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function active(): Collection;

    public function create(EmployeeStoreDTO $dto): Employee;

    public function update(Employee $employee, EmployeeStoreDTO $dto): Employee;
}