<?php

namespace App\Repositories;

use App\Contracts\EmployeeRepositoryInterface;
use App\DTOs\EmployeeStoreDTO;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Employee::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    public function active(): Collection
    {
        return Employee::query()->active()->orderBy('last_name')->get();
    }

    public function create(EmployeeStoreDTO $dto): Employee
    {
        return Employee::create($dto->toArray());
    }

    public function update(Employee $employee, EmployeeStoreDTO $dto): Employee
    {
        $employee->update($dto->toArray());

        return $employee;
    }
}
