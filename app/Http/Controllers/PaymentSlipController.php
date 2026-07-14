<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Services\PayrollService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentSlipController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search', ''),
            'period' => $request->input('period', ''),
            'per_page' => $request->input('per_page', 10),
        ];

        $period = $this->payrollService->parsePeriod($filters['period']);

        $slips = PayrollDetail::query()
            ->with(['payroll.status:id,code,name'])
            ->whereHas('payroll.status', fn(Builder $query) => $query->whereIn('code', [
                Payroll::STATUS_APPROVED,
                Payroll::STATUS_PAID,
            ]))
            ->when($filters['search'], function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            })
            ->when($period['month'], fn(Builder $query) => $query->whereHas('payroll', fn(Builder $payrollQuery) => $payrollQuery->where('month', $period['month'])))
            ->when($period['year'], fn(Builder $query) => $query->whereHas('payroll', fn(Builder $payrollQuery) => $payrollQuery->where('year', $period['year'])))
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? 10), 100))
            ->withQueryString()
            ->through(fn(PayrollDetail $detail) => $this->slipPayload($detail));

        return Inertia::render('PaymentSlips/Index', [
            'slips' => $slips,
            'filters' => $filters,
        ]);
    }

    public function print(PayrollDetail $paymentSlip): HttpResponse
    {
        $paymentSlip->loadMissing([
            'payroll.status:id,code,name',
            'concepts.conceptType:id,code,name',
        ]);

        abort_unless($this->isPrintable($paymentSlip), 404);

        return response()
            ->view('payment-slips.print', [
                'slip' => $this->slipPayload($paymentSlip, includeConcepts: true),
            ]);
    }

    public function pdf(PayrollDetail $paymentSlip): HttpResponse
    {
        $paymentSlip->loadMissing([
            'payroll.status:id,code,name',
            'concepts.conceptType:id,code,name',
        ]);

        abort_unless($this->isPrintable($paymentSlip), 404);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('payment-slips.print', [
            'slip' => $this->slipPayload($paymentSlip, includeConcepts: true),
        ])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->slipFilename($paymentSlip, 'pdf') . '"',
        ]);
    }

    public function excel(PayrollDetail $paymentSlip): StreamedResponse
    {
        $paymentSlip->loadMissing([
            'payroll.status:id,code,name',
            'concepts.conceptType:id,code,name',
        ]);

        abort_unless($this->isPrintable($paymentSlip), 404);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Boleta');

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Boleta de pago');
        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', $this->payrollService->monthName($paymentSlip->payroll->month) . ' ' . $paymentSlip->payroll->year . ' | ' . $paymentSlip->payroll->code);

        $sheet->fromArray([
            ['Trabajador', $paymentSlip->employee_name, '', 'Codigo', $paymentSlip->employee_code],
            ['Documento', $paymentSlip->document_number, '', 'Regimen', $paymentSlip->pension_system_name ?? 'Sin regimen'],
            ['Dias trabajados', $paymentSlip->worked_days, '', 'Horas extra', $paymentSlip->overtime_hours],
        ], null, 'A4');

        $sheet->fromArray(['Concepto', 'Tipo', 'Cantidad', 'Tasa', 'Importe'], null, 'A9');
        $row = 10;

        foreach ($paymentSlip->concepts->sortBy('sort_order') as $concept) {
            $sheet->fromArray([
                $concept->name,
                $concept->conceptType?->name,
                (float) $concept->quantity,
                (float) $concept->rate,
                (float) $concept->amount,
            ], null, "A{$row}");
            $row++;
        }

        $totalRow = $row + 1;
        $sheet->setCellValue("D{$totalRow}", 'Total ingresos');
        $sheet->setCellValue("E{$totalRow}", (float) $paymentSlip->total_income);
        $sheet->setCellValue('D' . ($totalRow + 1), 'Total descuentos');
        $sheet->setCellValue('E' . ($totalRow + 1), (float) $paymentSlip->total_discount);
        $sheet->setCellValue('D' . ($totalRow + 2), 'Neto a pagar');
        $sheet->setCellValue('E' . ($totalRow + 2), (float) $paymentSlip->net_pay);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF111827');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF6B7280');
        $sheet->getStyle('A4:E6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $sheet->getStyle('A9:E9')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A9:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $sheet->getStyle("E10:E" . ($totalRow + 2))->getNumberFormat()->setFormatCode('"S/ " #,##0.00');
        $sheet->getStyle('D' . ($totalRow + 2) . ':E' . ($totalRow + 2))->getFont()->setBold(true)->setSize(12);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $this->slipFilename($paymentSlip, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function slipPayload(PayrollDetail $detail, bool $includeConcepts = false): array
    {
        $payload = [
            'id' => $detail->id,
            'employee_name' => $detail->employee_name,
            'employee_code' => $detail->employee_code,
            'document_number' => $detail->document_number,
            'pension_system_name' => $detail->pension_system_name ?? 'Sin regimen',
            'period' => $this->payrollService->monthName($detail->payroll->month) . ' ' . $detail->payroll->year,
            'payroll_code' => $detail->payroll->code,
            'status' => [
                'code' => $detail->payroll->status?->code,
                'name' => $detail->payroll->status?->name,
            ],
            'base_salary' => $detail->base_salary,
            'worked_days' => $detail->worked_days,
            'absence_days' => $detail->absence_days,
            'uncompensated_absence_days' => $detail->uncompensated_absence_days,
            'overtime_hours' => $detail->overtime_hours,
            'total_income' => $detail->total_income,
            'total_discount' => $detail->total_discount,
            'total_employer_contribution' => $detail->total_employer_contribution,
            'net_pay' => $detail->net_pay,
        ];

        if ($includeConcepts) {
            $payload['concepts'] = $detail->concepts
                ->sortBy('sort_order')
                ->values()
                ->map(fn($concept) => [
                    'name' => $concept->name,
                    'type' => $concept->conceptType?->name,
                    'quantity' => $concept->quantity,
                    'rate' => $concept->rate,
                    'amount' => $concept->amount,
                    'is_income' => $concept->conceptType?->code === 'INCOME',
                ]);
        }

        return $payload;
    }

    private function isPrintable(PayrollDetail $detail): bool
    {
        return in_array($detail->payroll->status?->code, [
            Payroll::STATUS_APPROVED,
            Payroll::STATUS_PAID,
        ], true);
    }

    private function slipFilename(PayrollDetail $detail, string $extension): string
    {
        return sprintf(
            'boleta-%s-%04d-%02d.%s',
            $detail->employee_code,
            $detail->payroll->year,
            $detail->payroll->month,
            $extension,
        );
    }
}
