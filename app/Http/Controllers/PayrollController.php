<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\StorePayrollRequest;
use App\Models\Catalog;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $service
    ) {}

    /**
     * Muestra el listado y los controles principales de planillas.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'period' => $request->input('period', ''),
            'status_id' => $request->input('status_id', ''),
            'payroll_group_id' => $request->input('payroll_group_id', ''),
            'per_page' => $request->input('per_page', 10),
        ];

        $payrolls = $this->service
            ->paginate($filters)
            ->through(fn (Payroll $payroll) => $this->payrollPayload($payroll));

        return Inertia::render('Payrolls/Index', [
            'payrolls' => $payrolls,
            'filters' => $filters,
            'statuses' => $this->service->statuses(),
            'monthOptions' => $this->service->monthOptions(),
            'yearOptions' => $this->service->yearOptions(),
            'payrollGroupOptions' => $this->payrollGroupOptions(),
            'defaultPeriod' => now()->subMonthNoOverflow()->format('Y-m'),
        ]);
    }

    /**
     * Genera una planilla desde asistencias cerradas.
     */
    public function store(StorePayrollRequest $request): RedirectResponse
    {
        $payroll = $this->service->generate(
            $request->validated(),
            $request->user()?->id
        );

        return redirect()
            ->route('payrolls.index', [
                'period' => sprintf('%04d-%02d', $payroll->year, $payroll->month),
                'payroll_group_id' => $payroll->payroll_group_id,
            ])
            ->with('success', 'Planilla generada correctamente y enviada a revision.');
    }

    public function approve(Payroll $payroll, Request $request): RedirectResponse
    {
        $this->service->approve($payroll, $request->user()?->id);

        return back()->with('success', 'Planilla aprobada correctamente.');
    }

    public function reject(Payroll $payroll, Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->reject(
            $payroll,
            $request->user()?->id,
            $request->input('reason')
        );

        return back()->with('success', 'Planilla rechazada correctamente.');
    }

    public function observe(Payroll $payroll, Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->observe(
            $payroll,
            $request->user()?->id,
            $request->input('reason')
        );

        return back()->with('success', 'Planilla observada correctamente. Corrige las asistencias necesarias y recalcula la planilla.');
    }

    public function recalculate(Payroll $payroll, Request $request): RedirectResponse
    {
        $this->service->recalculate($payroll, $request->user()?->id);

        return back()->with('success', 'Planilla recalculada correctamente y enviada nuevamente a revision.');
    }

    public function pay(Payroll $payroll, Request $request): RedirectResponse
    {
        $this->service->markAsPaid($payroll, $request->user()?->id);

        return back()->with('success', 'Planilla marcada como pagada.');
    }

    public function paymentFile(Payroll $payroll): StreamedResponse
    {
        abort_unless(
            $payroll->isApproved() || $payroll->isPaid(),
            403,
            'El archivo de pago solo esta disponible para planillas aprobadas o pagadas.'
        );

        $payroll->loadMissing([
            'status:id,code,name',
            'payrollGroup:id,code,name',
            'details.employee.position:id,name',
            'details.employee.workArea:id,name',
            'details.employee.primaryBankAccount.bank:id,name,code',
            'details.employee.primaryBankAccount.accountType:id,name',
            'details.monthlyAttendance:id,worked_days,absence_days,compensated_absence_days,uncompensated_absence_days,exchange_days,overtime_hours,payable_overtime_hours',
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Archivo de pago');

        $columns = [
            'DNI',
            'Apellidos y nombres',
            'Codigo',
            'Cargo',
            'Area',
            'Banco',
            'Tipo de cuenta',
            'Numero de cuenta',
            'CCI',
            'Sueldo basico',
            'Dias trabajados',
            'Faltas',
            'Faltas descontables',
            'Canjes',
            'Horas extra',
            'Total ingresos',
            'Total descuentos',
            'Neto a pagar',
            'Observacion bancaria',
        ];

        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', 'Archivo de pago de planilla');
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', sprintf(
            'Planilla: %s | Periodo: %s %s | Grupo: %s | Estado: %s | Generado: %s',
            $payroll->code,
            $this->service->monthName($payroll->month),
            $payroll->year,
            $payroll->payrollGroup?->name ?? 'Sin grupo',
            $payroll->status?->name ?? 'Sin estado',
            now()->format('d-m-Y H:i')
        ));

        $sheet->fromArray($columns, null, 'A4');

        $rows = $payroll->details
            ->sortBy('employee_name')
            ->values()
            ->map(function ($detail) {
                $employee = $detail->employee;
                $attendance = $detail->monthlyAttendance;
                $account = $employee?->primaryBankAccount;

                return [
                    $detail->document_number,
                    $detail->employee_name,
                    $detail->employee_code,
                    $employee?->position?->name ?? 'Sin cargo',
                    $employee?->workArea?->name ?? 'Sin area',
                    $account?->bank?->name ?? 'Sin banco',
                    $account?->accountType?->name ?? 'Sin tipo',
                    $account?->account_number ?? 'Sin cuenta',
                    $account?->cci ?? '',
                    (float) $detail->base_salary,
                    (float) ($attendance?->worked_days ?? $detail->worked_days),
                    (float) ($attendance?->absence_days ?? $detail->absence_days),
                    (float) ($attendance?->uncompensated_absence_days ?? $detail->uncompensated_absence_days),
                    (float) ($attendance?->exchange_days ?? 0),
                    (float) ($attendance?->payable_overtime_hours ?? $detail->overtime_hours),
                    (float) $detail->total_income,
                    (float) $detail->total_discount,
                    (float) $detail->net_pay,
                    $account ? '' : 'Registrar cuenta bancaria principal antes de pagar',
                ];
            })
            ->all();

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 5;

            foreach ($row as $columnIndex => $value) {
                $excelColumn = Coordinate::stringFromColumnIndex($columnIndex + 1);

                if (in_array($columnIndex, [0, 7, 8], true)) {
                    $sheet->setCellValueExplicit("{$excelColumn}{$excelRow}", (string) $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue("{$excelColumn}{$excelRow}", $value);
                }
            }
        }

        $lastRow = max(count($rows) + 4, 5);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF111827');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF6B7280');
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF166534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB('FFE5E7EB');
        $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:{$lastColumn}{$lastRow}");

        foreach ([10, 16, 17, 18] as $columnIndex) {
            $letter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getStyle("{$letter}5:{$letter}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('"S/ " #,##0.00');
        }

        for ($column = 1; $column <= count($columns); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $filename = sprintf('archivo-pago-%s.xlsx', str($payroll->code)->lower()->replace(' ', '-')->toString());

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function payrollPayload(Payroll $payroll): array
    {
        $payroll->loadMissing([
            'status:id,code,name',
            'payrollGroup:id,code,name',
            'details.concepts.conceptType:id,code,name',
        ]);

        return [
            'id' => $payroll->id,
            'code' => $payroll->code,
            'period' => $this->service->monthName($payroll->month).' '.$payroll->year,
            'payroll_group' => [
                'id' => $payroll->payrollGroup?->id,
                'code' => $payroll->payrollGroup?->code,
                'name' => $payroll->payrollGroup?->name ?? 'Sin grupo',
            ],
            'month' => $payroll->month,
            'year' => $payroll->year,
            'payment_date' => $payroll->payment_date?->toDateString(),
            'status' => [
                'id' => $payroll->status?->id,
                'code' => $payroll->status?->code,
                'name' => $payroll->status?->name,
            ],
            'employee_count' => $payroll->employee_count,
            'total_base_salary' => $payroll->total_base_salary,
            'total_income' => $payroll->total_income,
            'total_discount' => $payroll->total_discount,
            'total_employer_contribution' => $payroll->total_employer_contribution,
            'total_net' => $payroll->total_net,
            'observations' => $payroll->observations,
            'rejection_reason' => $payroll->rejection_reason,
            'can_approve' => $payroll->isInReview(),
            'can_observe' => $payroll->isInReview(),
            'can_reject' => $payroll->isInReview(),
            'can_pay' => $payroll->isApproved(),
            'can_download_payment_file' => $payroll->isApproved() || $payroll->isPaid(),
            'can_recalculate' => $payroll->isObserved() || $payroll->isRejected(),
            'details' => $payroll->details
                ->sortBy('employee_name')
                ->values()
                ->map(fn ($detail) => [
                    'id' => $detail->id,
                    'employee_name' => $detail->employee_name,
                    'employee_code' => $detail->employee_code,
                    'document_number' => $detail->document_number,
                    'pension_system_name' => $detail->pension_system_name ?? 'Sin regimen',
                    'base_salary' => $detail->base_salary,
                    'worked_days' => $detail->worked_days,
                    'absence_days' => $detail->absence_days,
                    'uncompensated_absence_days' => $detail->uncompensated_absence_days,
                    'overtime_hours' => $detail->overtime_hours,
                    'total_income' => $detail->total_income,
                    'total_discount' => $detail->total_discount,
                    'total_employer_contribution' => $detail->total_employer_contribution,
                    'net_pay' => $detail->net_pay,
                    'concepts' => $detail->concepts
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn ($concept) => [
                            'id' => $concept->id,
                            'type' => [
                                'code' => $concept->conceptType?->code,
                                'name' => $concept->conceptType?->name,
                            ],
                            'code' => $concept->code,
                            'name' => $concept->name,
                            'quantity' => $concept->quantity,
                            'rate' => $concept->rate,
                            'amount' => $concept->amount,
                        ]),
                ]),
        ];
    }

    private function payrollGroupOptions()
    {
        return Catalog::query()
            ->where('type', 'PAYROLL_GROUP')
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }
}
