<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\PlameExportService;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly PlameExportService $plameExport,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Reports/Index');
    }

    public function exportPlame(Payroll $payroll): HttpResponse
    {
        return response($this->plameExport->buildTxt($payroll->id), 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' .
                $this->plameExport->filename($payroll->period_year, $payroll->period_month) . '"',
        ]);
    }
}
