<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkShift;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialProductionDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_required_roles_workers_and_shifts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('catalogs', [
            'type' => 'POSITION',
            'code' => 'HUMAN_RESOURCES',
            'name' => 'Responsable de RRHH',
        ]);

        $this->assertDatabaseHas('catalogs', [
            'type' => 'POSITION',
            'code' => 'GENERAL_MANAGER',
            'name' => 'Gerente',
        ]);

        foreach ([
            '70000001' => 'Administrador',
            '70000002' => 'RRHH',
            '70000003' => 'Contabilidad',
            '70000004' => 'Gerente',
        ] as $document => $role) {
            $user = User::query()->where('username', $document)->first();

            $this->assertNotNull($user);
            $this->assertTrue($user->hasRole($role));
            $this->assertDatabaseHas('employees', [
                'document_number' => $document,
                'user_id' => $user->id,
            ]);
        }

        $this->assertDatabaseMissing('employees', [
            'document_number' => 'admin',
        ]);

        $productionGroup = Catalog::query()
            ->where('type', 'PAYROLL_GROUP')
            ->where('code', 'PRODUCTION')
            ->firstOrFail();

        $this->assertSame(
            10,
            Employee::query()->where('payroll_group_id', $productionGroup->id)->count()
        );

        $watchman = Employee::query()
            ->where('document_number', '72987415')
            ->with(['position', 'workArea', 'payrollGroup', 'workShift'])
            ->firstOrFail();

        $this->assertSame('Vigilante', $watchman->position->name);
        $this->assertSame('Seguridad', $watchman->workArea->name);
        $this->assertSame('Planilla personal de produccion', $watchman->payrollGroup->name);
        $this->assertSame('Turno Noche Rotativo 6x1', $watchman->workShift->name);
        $this->assertTrue($watchman->workShift->rotation_enabled);
        $this->assertSame(6, $watchman->workShift->rotation_work_days);
        $this->assertSame(1, $watchman->workShift->rotation_rest_days);

        $morningShift = WorkShift::query()->where('name', 'Turno Mañana')->firstOrFail();

        $this->assertSame(
            9,
            Employee::query()
                ->where('payroll_group_id', $productionGroup->id)
                ->where('work_shift_id', $morningShift->id)
                ->count()
        );

        $this->assertSame(
            14,
            Employee::query()
                ->whereHas('primaryBankAccount.bank', fn ($query) => $query->where('code', 'BCP'))
                ->count()
        );
    }
}
