<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/ubigeo/distritos.json');

        $districts = collect(
            json_decode(File::get($path), true)
        )
        ->map(fn ($district) => [
            'id' => (int) $district['id'],
            'province_id' => (int) $district['province_id'],
            'name' => mb_strtoupper($district['name'], 'UTF-8'),
            'created_at' => now(),
            'updated_at' => now(),
        ])
        ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Upsert por lotes
        |--------------------------------------------------------------------------
        | Evita problemas de memoria
        | cuando existen más de 1800 distritos y permite repetir el seeder
        | en producción sin romper llaves foráneas.
        */

        foreach (array_chunk($districts, 500) as $chunk) {
            District::query()->upsert(
                $chunk,
                ['id'],
                ['province_id', 'name', 'updated_at']
            );
        }

        $this->command->info(
            count($districts).' distritos importados.'
        );
    }
}
