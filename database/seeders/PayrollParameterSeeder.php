<?php

namespace Database\Seeders;

use App\Models\PayrollParameter;
use Illuminate\Database\Seeder;

class PayrollParameterSeeder extends Seeder
{
    /**
     * Registra valores iniciales editables para el calculo de planillas.
     */
    public function run(): void
    {
        $parameters = [
            [
                'code' => 'ONP_RATE',
                'name' => 'Tasa ONP',
                'value' => 0.1300,
                'description' => 'Descuento pensionario ONP aplicado al trabajador.',
            ],
            [
                'code' => 'AFP_RATE',
                'name' => 'Tasa AFP referencial',
                'value' => 0.1300,
                'description' => 'Tasa referencial para AFP hasta configurar comisiones por entidad.',
            ],
            [
                'code' => 'ESSALUD_RATE',
                'name' => 'Aporte EsSalud',
                'value' => 0.0900,
                'description' => 'Aporte del empleador calculado sobre la remuneracion.',
            ],
            [
                'code' => 'OVERTIME_RATE',
                'name' => 'Factor hora extra',
                'value' => 1.2500,
                'description' => 'Factor inicial usado para valorizar horas extra.',
            ],
        ];

        foreach ($parameters as $parameter) {
            PayrollParameter::updateOrCreate(
                ['code' => $parameter['code']],
                [
                    'name' => $parameter['name'],
                    'value' => $parameter['value'],
                    'description' => $parameter['description'],
                    'effective_from' => now()->startOfYear()->toDateString(),
                    'status' => true,
                ]
            );
        }
    }
}
