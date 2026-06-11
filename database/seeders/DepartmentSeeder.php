<?php
namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/ubigeo/departamentos.json');

        $departments = collect(
            json_decode(File::get($path), true)
        )
            ->map(fn($department) => [
                'id'         => (int) $department['id'],
                'name'       => strtoupper($department['name']),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        Department::query()->delete();

        Department::insert($departments);

        $this->command->info(
            count($departments) . ' departamentos importados.'
        );
    }
}
