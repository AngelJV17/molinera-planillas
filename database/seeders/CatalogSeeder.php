<?php
namespace Database\Seeders;

use App\Models\Catalog;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Registra opciones iniciales del sistema.
     *
     * Estos catálogos reemplazan el uso de ENUM en base de datos,
     * permitiendo agregar nuevas opciones sin modificar migraciones.
     */
    public function run(): void
    {
        $catalogs = [
            /*
            |--------------------------------------------------------------------------
            | TIPOS DE DOCUMENTO
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'DOCUMENT_TYPE',
                'code'        => 'DNI',
                'name'        => 'DNI',
                'description' => 'Documento Nacional de Identidad',
            ],
            [
                'type'        => 'DOCUMENT_TYPE',
                'code'        => 'CE',
                'name'        => 'Carné de Extranjería',
                'description' => 'Documento para trabajadores extranjeros',
            ],
            [
                'type'        => 'DOCUMENT_TYPE',
                'code'        => 'PASSPORT',
                'name'        => 'Pasaporte',
                'description' => 'Documento de identificación internacional',
            ],

            /*
            |--------------------------------------------------------------------------
            | GÉNEROS
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'GENDER',
                'code'        => 'MALE',
                'name'        => 'Masculino',
                'description' => 'Género masculino',
            ],
            [
                'type'        => 'GENDER',
                'code'        => 'FEMALE',
                'name'        => 'Femenino',
                'description' => 'Género femenino',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADOS CIVILES
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'MARITAL_STATUS',
                'code'        => 'SINGLE',
                'name'        => 'Soltero(a)',
                'description' => 'Persona que no ha contraído matrimonio',
            ],
            [
                'type'        => 'MARITAL_STATUS',
                'code'        => 'MARRIED',
                'name'        => 'Casado(a)',
                'description' => 'Persona unida legalmente en matrimonio',
            ],
            [
                'type'        => 'MARITAL_STATUS',
                'code'        => 'COHABITANT',
                'name'        => 'Conviviente',
                'description' => 'Persona que mantiene una unión de hecho',
            ],
            [
                'type'        => 'MARITAL_STATUS',
                'code'        => 'DIVORCED',
                'name'        => 'Divorciado(a)',
                'description' => 'Persona cuyo matrimonio ha sido disuelto legalmente',
            ],
            [
                'type'        => 'MARITAL_STATUS',
                'code'        => 'WIDOWED',
                'name'        => 'Viudo(a)',
                'description' => 'Persona cuyo cónyuge ha fallecido',
            ],

            /*
            |--------------------------------------------------------------------------
            | ÁREAS DE TRABAJO
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'WORK_AREA',
                'code'        => 'ADMIN',
                'name'        => 'Administración',
                'description' => 'Área encargada de la gestión administrativa de la empresa',
            ],
            [
                'type'        => 'WORK_AREA',
                'code'        => 'PRODUCTION',
                'name'        => 'Producción',
                'description' => 'Área encargada del proceso operativo del molino',
            ],
            [
                'type'        => 'WORK_AREA',
                'code'        => 'WAREHOUSE',
                'name'        => 'Almacén',
                'description' => 'Área encargada de recepción, control e inventario',
            ],
            [
                'type'        => 'WORK_AREA',
                'code'        => 'SECURITY',
                'name'        => 'Seguridad',
                'description' => 'Área encargada de vigilancia y control de ingreso',
            ],
            [
                'type'        => 'WORK_AREA',
                'code'        => 'ACCOUNTING',
                'name'        => 'Contabilidad',
                'description' => 'Área encargada de la gestión contable y financiera',
            ],

            /*
            |--------------------------------------------------------------------------
            | CARGOS
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'POSITION',
                'code'        => 'MANAGER',
                'name'        => 'Administrador',
                'description' => 'Responsable de la gestión general de la empresa',
            ],
            [
                'type'        => 'POSITION',
                'code'        => 'ACCOUNTANT',
                'name'        => 'Contador',
                'description' => 'Responsable de la contabilidad de la empresa',
            ],
            [
                'type'        => 'POSITION',
                'code'        => 'SUPERVISOR',
                'name'        => 'Supervisor',
                'description' => 'Responsable de supervisar las labores operativas',
            ],
            [
                'type'        => 'POSITION',
                'code'        => 'MILL_OPERATOR',
                'name'        => 'Operario de Molino',
                'description' => 'Trabajador encargado de apoyar en las operaciones del molino',
            ],
            [
                'type'        => 'POSITION',
                'code'        => 'WAREHOUSE_ASSISTANT',
                'name'        => 'Auxiliar de Almacén',
                'description' => 'Apoyo en inventario, recepción y almacenamiento',
            ],
            [
                'type'        => 'POSITION',
                'code'        => 'ADMIN_ASSISTANT',
                'name'        => 'Asistente Administrativo',
                'description' => 'Apoyo en tareas administrativas de la empresa',
            ],
            [
                'type'        => 'POSITION',
                'code'        => 'WATCHMAN',
                'name'        => 'Vigilante',
                'description' => 'Responsable de la seguridad y control de ingreso',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADOS DEL TRABAJADOR
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'WORKER_STATUS',
                'code'        => 'ACTIVE',
                'name'        => 'Activo',
                'description' => 'Trabajador actualmente laborando',
            ],
            [
                'type'        => 'WORKER_STATUS',
                'code'        => 'ON_VACATION',
                'name'        => 'Vacaciones',
                'description' => 'Trabajador que se encuentra de vacaciones',
            ],
            [
                'type'        => 'WORKER_STATUS',
                'code'        => 'ON_LEAVE',
                'name'        => 'Licencia',
                'description' => 'Trabajador con licencia temporal',
            ],
            [
                'type'        => 'WORKER_STATUS',
                'code'        => 'SUSPENDED',
                'name'        => 'Suspendido',
                'description' => 'Trabajador suspendido temporalmente',
            ],
            [
                'type'        => 'WORKER_STATUS',
                'code'        => 'TERMINATED',
                'name'        => 'Cesado',
                'description' => 'Trabajador que ya no pertenece a la empresa',
            ],

            /*
            |--------------------------------------------------------------------------
            | SISTEMA PENSIONARIO
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'ONP',
                'name'        => 'ONP',
                'description' => 'Sistema Nacional de Pensiones',
            ],
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'AFP_INTEGRA',
                'name'        => 'AFP Integra',
                'description' => 'Administradora Privada de Fondos de Pensiones',
            ],
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'AFP_PRIMA',
                'name'        => 'AFP Prima',
                'description' => 'Administradora Privada de Fondos de Pensiones',
            ],
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'AFP_PROFUTURO',
                'name'        => 'AFP Profuturo',
                'description' => 'Administradora Privada de Fondos de Pensiones',
            ],
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'AFP_HABITAT',
                'name'        => 'AFP Habitat',
                'description' => 'Administradora Privada de Fondos de Pensiones',
            ],
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'NONE',
                'name'        => 'Ninguno',
                'description' => 'No registra régimen pensionario',
            ],

            /*
            |--------------------------------------------------------------------------
            | TIPOS DE CUENTA BANCARIA
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'ACCOUNT_TYPE',
                'code'        => 'SAVINGS',
                'name'        => 'Cuenta de Ahorros',
                'description' => 'Cuenta bancaria para depósitos y retiros',
            ],
            [
                'type'        => 'ACCOUNT_TYPE',
                'code'        => 'CURRENT',
                'name'        => 'Cuenta Corriente',
                'description' => 'Cuenta bancaria utilizada para operaciones empresariales',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADOS DE ASISTENCIA
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'ATTENDANCE_STATUS',
                'code'        => 'PRESENT',
                'name'        => 'Asistió',
                'description' => 'El trabajador asistió en la fecha registrada',
            ],
            [
                'type'        => 'ATTENDANCE_STATUS',
                'code'        => 'ABSENT',
                'name'        => 'Faltó',
                'description' => 'El trabajador no asistió en la fecha registrada',
            ],
            [
                'type'        => 'ATTENDANCE_STATUS',
                'code'        => 'REST',
                'name'        => 'Descanso',
                'description' => 'Día no laborable o descanso del trabajador',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADOS DE PLANILLA
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'PAYROLL_STATUS',
                'code'        => 'GENERATED',
                'name'        => 'Generada',
                'description' => 'Planilla generada por el administrador',
            ],
            [
                'type'        => 'PAYROLL_STATUS',
                'code'        => 'IN_REVIEW',
                'name'        => 'En revisión',
                'description' => 'Planilla pendiente de revisión por gerencia',
            ],
            [
                'type'        => 'PAYROLL_STATUS',
                'code'        => 'APPROVED',
                'name'        => 'Aprobada',
                'description' => 'Planilla aprobada por gerencia',
            ],
            [
                'type'        => 'PAYROLL_STATUS',
                'code'        => 'REJECTED',
                'name'        => 'Rechazada',
                'description' => 'Planilla rechazada por gerencia',
            ],
            [
                'type'        => 'PAYROLL_STATUS',
                'code'        => 'PAID',
                'name'        => 'Pagada',
                'description' => 'Planilla pagada de forma externa al sistema',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADOS DE BOLETA
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'PAYMENT_SLIP_STATUS',
                'code'        => 'GENERATED',
                'name'        => 'Generada',
                'description' => 'Boleta generada por el sistema',
            ],
            [
                'type'        => 'PAYMENT_SLIP_STATUS',
                'code'        => 'DOWNLOADED',
                'name'        => 'Descargada',
                'description' => 'Boleta descargada por el usuario autorizado',
            ],
            [
                'type'        => 'PAYMENT_SLIP_STATUS',
                'code'        => 'CANCELLED',
                'name'        => 'Anulada',
                'description' => 'Boleta anulada por corrección administrativa',
            ],

            /*
            |--------------------------------------------------------------------------
            | TIPOS DE CONCEPTO PARA BOLETA
            |--------------------------------------------------------------------------
            */
            [
                'type'        => 'PAYMENT_CONCEPT_TYPE',
                'code'        => 'INCOME',
                'name'        => 'Ingreso',
                'description' => 'Concepto que incrementa el pago del trabajador',
            ],
            [
                'type'        => 'PAYMENT_CONCEPT_TYPE',
                'code'        => 'DISCOUNT',
                'name'        => 'Descuento',
                'description' => 'Concepto que descuenta el pago del trabajador',
            ],
            [
                'type'        => 'PAYMENT_CONCEPT_TYPE',
                'code'        => 'EMPLOYER_CONTRIBUTION',
                'name'        => 'Aporte del empleador',
                'description' => 'Concepto asumido por el empleador',
            ],
        ];

        foreach ($catalogs as $catalog) {
            Catalog::updateOrCreate(
                [
                    'type' => $catalog['type'],
                    'code' => $catalog['code'],
                ],
                [
                    'name'        => $catalog['name'],
                    'description' => $catalog['description'],
                    'status'      => true,
                ]
            );
        }
    }
}
