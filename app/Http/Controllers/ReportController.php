<?php

namespace App\Http\Controllers;

use App\Models\MonthlyAttendance;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Services\PayrollService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'payroll_summary',
        'attendance_summary',
        'payment_slips',
    ];

    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'period' => $request->input('period', now()->subMonthNoOverflow()->format('Y-m')),
        ];

        $period = $this->payrollService->parsePeriod($filters['period']);

        return Inertia::render('Reports/Index', [
            'filters' => $filters,
            'reports' => $this->availableReports($period),
        ]);
    }

    public function export(Request $request): StreamedResponse|HttpResponse
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', self::REPORT_TYPES)],
            'format' => ['required', 'in:xlsx,pdf'],
            'period' => ['nullable', 'regex:/^(\d{4}-\d{2}|\d{2}-\d{4})$/'],
        ]);

        $type = $request->string('type')->toString();
        $format = $request->string('format')->toString();
        $periodInput = $this->normalizePeriodInput($request->input('period', now()->format('Y-m')));
        $report = $this->reportData($type, $this->payrollService->parsePeriod($periodInput));

        return $format === 'pdf'
            ? $this->pdfResponse($report, $periodInput)
            : $this->excelResponse($report, $periodInput);
    }

    private function availableReports(array $period): array
    {
        $payrolls = Payroll::query()
            ->when($period['month'], fn (Builder $query) => $query->where('month', $period['month']))
            ->when($period['year'], fn (Builder $query) => $query->where('year', $period['year']));

        $attendances = MonthlyAttendance::query()
            ->when($period['month'], fn (Builder $query) => $query->where('month', $period['month']))
            ->when($period['year'], fn (Builder $query) => $query->where('year', $period['year']));

        $slips = PayrollDetail::query()
            ->whereHas('payroll.status', fn (Builder $query) => $query->whereIn('code', [
                Payroll::STATUS_APPROVED,
                Payroll::STATUS_PAID,
            ]))
            ->when($period['month'], fn (Builder $query) => $query->whereHas('payroll', fn (Builder $payrollQuery) => $payrollQuery->where('month', $period['month'])))
            ->when($period['year'], fn (Builder $query) => $query->whereHas('payroll', fn (Builder $payrollQuery) => $payrollQuery->where('year', $period['year'])));

        return [
            [
                'type' => 'payroll_summary',
                'name' => 'Resumen de planillas',
                'description' => 'Totales de ingresos, descuentos, aportes y neto por planilla.',
                'records' => (clone $payrolls)->count(),
            ],
            [
                'type' => 'attendance_summary',
                'name' => 'Resumen de asistencias',
                'description' => 'Dias trabajados, faltas, canjes y horas extra por trabajador.',
                'records' => (clone $attendances)->count(),
            ],
            [
                'type' => 'payment_slips',
                'name' => 'Boletas emitidas',
                'description' => 'Detalle exportable de boletas disponibles para el periodo.',
                'records' => (clone $slips)->count(),
            ],
        ];
    }

    private function normalizePeriodInput(?string $period): string
    {
        $period = trim(str_replace('/', '-', (string) $period));

        if (preg_match('/^\d{2}-\d{4}$/', $period)) {
            [$month, $year] = explode('-', $period);

            return "{$year}-{$month}";
        }

        return $period;
    }

    private function reportData(string $type, array $period): array
    {
        return match ($type) {
            'payroll_summary' => $this->payrollSummaryData($period),
            'attendance_summary' => $this->attendanceSummaryData($period),
            'payment_slips' => $this->paymentSlipsData($period),
        };
    }

    private function payrollSummaryData(array $period): array
    {
        $rows = Payroll::query()
            ->with('status:id,code,name')
            ->when($period['month'], fn (Builder $query) => $query->where('month', $period['month']))
            ->when($period['year'], fn (Builder $query) => $query->where('year', $period['year']))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn (Payroll $payroll) => [
                $payroll->code,
                $this->payrollService->monthName($payroll->month).' '.$payroll->year,
                $payroll->status?->name,
                $payroll->employee_count,
                (float) $payroll->total_income,
                (float) $payroll->total_discount,
                (float) $payroll->total_employer_contribution,
                (float) $payroll->total_net,
                $payroll->payment_date?->format('d-m-Y') ?? '',
            ])
            ->values()
            ->all();

        return [
            'type' => 'payroll_summary',
            'title' => 'Resumen de planillas',
            'description' => 'Totales consolidados por planilla.',
            'columns' => ['Codigo', 'Periodo', 'Estado', 'Trabajadores', 'Total ingresos', 'Total descuentos', 'Aporte empleador', 'Neto', 'Fecha de pago'],
            'money_columns' => [5, 6, 7, 8],
            'rows' => $rows,
        ];
    }

    private function attendanceSummaryData(array $period): array
    {
        $rows = MonthlyAttendance::query()
            ->with(['employee:id,employee_code,first_name,last_name', 'status:id,code,name'])
            ->when($period['month'], fn (Builder $query) => $query->where('month', $period['month']))
            ->when($period['year'], fn (Builder $query) => $query->where('year', $period['year']))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn (MonthlyAttendance $attendance) => [
                $attendance->employee?->full_name,
                $attendance->employee?->employee_code,
                $this->payrollService->monthName($attendance->month).' '.$attendance->year,
                $attendance->status?->name,
                $attendance->worked_days,
                $attendance->absence_days,
                $attendance->compensated_absence_days,
                $attendance->uncompensated_absence_days,
                $attendance->exchange_days,
                (float) $attendance->overtime_hours,
                (float) $attendance->payable_overtime_hours,
            ])
            ->values()
            ->all();

        return [
            'type' => 'attendance_summary',
            'title' => 'Resumen de asistencias',
            'description' => 'Control mensual de asistencia por trabajador.',
            'columns' => ['Trabajador', 'Codigo', 'Periodo', 'Estado', 'Dias trabajados', 'Faltas', 'Faltas compensadas', 'Faltas por descontar', 'Canjes', 'Horas extra registradas', 'Horas extra remunerables'],
            'money_columns' => [],
            'rows' => $rows,
        ];
    }

    private function paymentSlipsData(array $period): array
    {
        $rows = PayrollDetail::query()
            ->with('payroll.status:id,code,name')
            ->whereHas('payroll.status', fn (Builder $query) => $query->whereIn('code', [
                Payroll::STATUS_APPROVED,
                Payroll::STATUS_PAID,
            ]))
            ->when($period['month'], fn (Builder $query) => $query->whereHas('payroll', fn (Builder $payrollQuery) => $payrollQuery->where('month', $period['month'])))
            ->when($period['year'], fn (Builder $query) => $query->whereHas('payroll', fn (Builder $payrollQuery) => $payrollQuery->where('year', $period['year'])))
            ->get()
            ->map(fn (PayrollDetail $detail) => [
                $detail->employee_name,
                $detail->employee_code,
                $detail->document_number,
                $detail->payroll->code,
                $this->payrollService->monthName($detail->payroll->month).' '.$detail->payroll->year,
                (float) $detail->total_income,
                (float) $detail->total_discount,
                (float) $detail->net_pay,
            ])
            ->values()
            ->all();

        return [
            'type' => 'payment_slips',
            'title' => 'Boletas emitidas',
            'description' => 'Resumen de boletas disponibles para entrega.',
            'columns' => ['Trabajador', 'Codigo', 'Documento', 'Planilla', 'Periodo', 'Ingresos', 'Descuentos', 'Neto'],
            'money_columns' => [6, 7, 8],
            'rows' => $rows,
        ];
    }

    private function excelResponse(array $report, string $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($report['title'], 0, 31));

        $lastColumn = Coordinate::stringFromColumnIndex(count($report['columns']));
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', $report['title']);
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', 'Periodo: '.($period ?: 'Todos').' | Generado: '.now()->format('d-m-Y H:i'));
        $sheet->fromArray($report['columns'], null, 'A4');
        $sheet->fromArray($report['rows'], null, 'A5');

        $lastRow = max(count($report['rows']) + 4, 5);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF111827');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF6B7280');
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:{$lastColumn}{$lastRow}");

        foreach ($report['money_columns'] as $columnIndex) {
            $letter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getStyle("{$letter}5:{$letter}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('"S/ " #,##0.00');
        }

        for ($column = 1; $column <= count($report['columns']); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $filename = $this->filename($report['type'], $period, 'xlsx');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function pdfResponse(array $report, string $period): HttpResponse
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.export-pdf', [
            'report' => $report,
            'period' => $period ?: 'Todos',
            'generatedAt' => now()->format('d-m-Y H:i'),
        ])->render());
        $dompdf->setPaper('A4', count($report['columns']) > 8 ? 'landscape' : 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($report['type'], $period, 'pdf').'"',
        ]);
    }

    private function filename(string $type, string $period, string $extension): string
    {
        return sprintf('%s-%s.%s', str_replace('_', '-', $type), $period ?: now()->format('Y-m'), $extension);
    }
}
