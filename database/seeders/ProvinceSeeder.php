<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/ubigeo/provincias.json');

        $provinces = collect(
            json_decode(File::get($path), true)
        )
        ->map(fn ($province) => [
            'id' => (int) $province['id'],
            'department_id' => (int) $province['department_id'],
            'name' => mb_strtoupper($province['name'], 'UTF-8'),
            'created_at' => now(),
            'updated_at' => now(),
        ])
        ->toArray();

        Province::query()->upsert(
            $provinces,
            ['id'],
            ['department_id', 'name', 'updated_at']
        );

        $this->command->info(
            count($provinces).' provincias importadas.'
        );
    }
}
