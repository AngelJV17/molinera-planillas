<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boleta {{ $slip['payroll_code'] }} - {{ $slip['employee_code'] }}</title>
    <style>
        body {
            color: #111827;
            font-family: Arial, sans-serif;
            margin: 32px;
        }

        .header {
            border-bottom: 2px solid #111827;
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 6px;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
        }

        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 24px;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
        }

        .label {
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 4px;
        }

        table {
            border-collapse: collapse;
            margin-top: 12px;
            width: 100%;
        }

        th, td {
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
        }

        .right {
            text-align: right;
        }

        .totals {
            margin-left: auto;
            margin-top: 24px;
            width: 320px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .net {
            border-top: 2px solid #111827;
            font-size: 18px;
            font-weight: 800;
        }

        @media print {
            body {
                margin: 18mm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Boleta de pago</h1>
            <div class="muted">{{ $slip['period'] }} · {{ $slip['payroll_code'] }}</div>
        </div>
        <div class="muted">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <section class="grid">
        <div class="box">
            <div class="label">Trabajador</div>
            <div class="value">{{ $slip['employee_name'] }}</div>
        </div>
        <div class="box">
            <div class="label">Documento</div>
            <div class="value">{{ $slip['document_number'] }}</div>
        </div>
        <div class="box">
            <div class="label">Codigo</div>
            <div class="value">{{ $slip['employee_code'] }}</div>
        </div>
        <div class="box">
            <div class="label">Regimen pensionario</div>
            <div class="value">{{ $slip['pension_system_name'] }}</div>
        </div>
    </section>

    <section>
        <h2>Conceptos</h2>
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Tipo</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Tasa</th>
                    <th class="right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($slip['concepts'] as $concept)
                    <tr>
                        <td>{{ $concept['name'] }}</td>
                        <td>{{ $concept['type'] }}</td>
                        <td class="right">{{ number_format((float) $concept['quantity'], 2) }}</td>
                        <td class="right">{{ number_format((float) $concept['rate'], 4) }}</td>
                        <td class="right">S/ {{ number_format((float) $concept['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="totals">
        <div class="total-row">
            <span>Total ingresos</span>
            <strong>S/ {{ number_format((float) $slip['total_income'], 2) }}</strong>
        </div>
        <div class="total-row">
            <span>Total descuentos</span>
            <strong>S/ {{ number_format((float) $slip['total_discount'], 2) }}</strong>
        </div>
        <div class="total-row net">
            <span>Neto a pagar</span>
            <span>S/ {{ number_format((float) $slip['net_pay'], 2) }}</span>
        </div>
    </section>
</body>
</html>
