<?php
namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'code'   => 'BCP',
                'name'   => 'Banco de Crédito del Perú',
                'status' => true,
            ],
            [
                'code'   => 'BBVA',
                'name'   => 'Banco Bilbao Vizcaya Argentaria S.A.',
                'status' => true,
            ],
            [
                'code'   => 'INTERBANK',
                'name'   => 'Banco Internacional del Perú Interbank',
                'status' => true,
            ],
            [
                'code'   => 'SCOTIABANK',
                'name'   => 'Scotiabank Perú S.A.A.',
                'status' => true,
            ],
            [
                'code'   => 'BANBIF',
                'name'   => 'Banco Interamericano de Finanzas',
                'status' => true,
            ],
            [
                'code'   => 'NACION',
                'name'   => 'Banco de la Nación',
                'status' => true,
            ],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(
                [
                    'code' => $bank['code'],
                ],
                [
                    'name'   => $bank['name'],
                    'status' => $bank['status'],
                ]
            );
        }

        $this->command->info(
            count($banks) . ' bancos registrados correctamente.'
        );
    }
}
