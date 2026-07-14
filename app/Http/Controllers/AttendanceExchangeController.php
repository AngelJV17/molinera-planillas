<?php

namespace App\Http\Controllers;

use App\Models\AttendanceExchange;
use App\Models\Catalog;
use App\Services\PayrollService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceExchangeController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search', ''),
            'status_id' => $request->input('status_id', ''),
            'period' => $request->input('period', ''),
            'per_page' => $request->input('per_page', 10),
        ];

        $period = $this->payrollService->parsePeriod($filters['period']);

        $exchanges = AttendanceExchange::query()
            ->with([
                'employee:id,employee_code,first_name,last_name,document_number',
                'status:id,code,name',
                'registeredBy:id,name',
            ])
            ->when($filters['search'], function (Builder $query, string $search) {
                $query->whereHas('employee', function (Builder $employeeQuery) use ($search) {
                    $employeeQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status_id'], fn(Builder $query) => $query->where('status_id', $filters['status_id']))
            ->when($period['month'], fn(Builder $query) => $query->whereMonth('absence_date', $period['month']))
            ->when($period['year'], fn(Builder $query) => $query->whereYear('absence_date', $period['year']))
            ->latest('absence_date')
            ->paginate(min((int) ($filters['per_page'] ?? 10), 100))
            ->withQueryString()
            ->through(fn(AttendanceExchange $exchange) => [
                'id' => $exchange->id,
                'employee' => [
                    'name' => $exchange->employee?->full_name,
                    'code' => $exchange->employee?->employee_code,
                    'document_number' => $exchange->employee?->document_number,
                ],
                'status' => [
                    'id' => $exchange->status?->id,
                    'code' => $exchange->status?->code,
                    'name' => $exchange->status?->name,
                ],
                'absence_date' => $exchange->absence_date?->toDateString(),
                'exchange_date' => $exchange->exchange_date?->toDateString(),
                'observation' => $exchange->observation,
                'registered_by' => $exchange->registeredBy?->name,
            ]);

        return Inertia::render('AttendanceExchanges/Index', [
            'exchanges' => $exchanges,
            'filters' => $filters,
            'statuses' => Catalog::query()
                ->where('type', AttendanceExchange::CATALOG_TYPE_STATUS)
                ->orderBy('catalogs.name')
                ->get(['id', 'code', 'name']),
        ]);
    }
}
