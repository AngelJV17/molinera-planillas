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

        foreach ($districts as $district) {
            District::updateOrCreate(
                ['id' => $district['id']],
                $district
            );
        }

        $this->command->info(
            count($districts).' distritos importados.'
        );
    }
}
