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
            // Tipos de documento
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

            // Estado del trabajador
            [
                'type'        => 'WORKER_STATUS',
                'code'        => 'ACTIVE',
                'name'        => 'Activo',
                'description' => 'Trabajador actualmente laborando',
            ],
            [
                'type'        => 'WORKER_STATUS',
                'code'        => 'INACTIVE',
                'name'        => 'Inactivo',
                'description' => 'Trabajador que ya no labora en la empresa',
            ],

            // Régimen pensionario
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'ONP',
                'name'        => 'ONP',
                'description' => 'Sistema Nacional de Pensiones',
            ],
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'AFP',
                'name'        => 'AFP',
                'description' => 'Sistema Privado de Pensiones',
            ],
            [
                'type'        => 'PENSION_SYSTEM',
                'code'        => 'NONE',
                'name'        => 'Ninguno',
                'description' => 'No registra régimen pensionario',
            ],

            // Estados de asistencia
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

            // Estados de planilla
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

            // Estados de boleta
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

            // Tipos de concepto para boleta
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
