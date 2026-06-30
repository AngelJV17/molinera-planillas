<?php

namespace App\Services;

use App\Models\PayrollParameter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PayrollParameterService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return PayrollParameter::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                isset($filters['status']) && $filters['status'] !== '',
                fn($query) => $query->where('status', (bool) $filters['status'])
            )
            ->orderBy('code')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    public function create(array $data): PayrollParameter
    {
        return PayrollParameter::create($data);
    }

    public function update(PayrollParameter $parameter, array $data): PayrollParameter
    {
        $parameter->update($data);

        return $parameter;
    }

    public function toggleStatus(PayrollParameter $parameter): PayrollParameter
    {
        $parameter->update([
            'status' => ! $parameter->status,
        ]);

        return $parameter;
    }
}
