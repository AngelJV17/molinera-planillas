<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
        }

        .header {
            border-bottom: 2px solid #0f766e;
            margin-bottom: 16px;
            padding-bottom: 10px;
        }

        h1 {
            color: #0f766e;
            font-size: 20px;
            margin: 0;
        }

        .meta {
            color: #6b7280;
            margin-top: 4px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #0f766e;
            color: #ffffff;
            font-size: 9px;
            padding: 7px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #f9fafb;
        }

        .right {
            text-align: right;
        }

        .empty {
            border: 1px dashed #cbd5e1;
            color: #64748b;
            padding: 18px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $report['title'] }}</h1>
        <div class="meta">{{ $report['description'] }}</div>
        <div class="meta">Periodo: {{ $period }} | Generado: {{ $generatedAt }}</div>
    </header>

    @if (count($report['rows']) === 0)
        <div class="empty">No hay informacion disponible para este periodo.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($report['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $index => $value)
                            <td class="{{ in_array($index + 1, $report['money_columns'], true) ? 'right' : '' }}">
                                @if (in_array($index + 1, $report['money_columns'], true))
                                    S/ {{ number_format((float) $value, 2) }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
