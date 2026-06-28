<?php

use App\Models\MonthlyAttendance;
use App\Models\WorkShift;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_days', function (Blueprint $table) {
             $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELACIÓN CON LA ASISTENCIA MENSUAL
            |--------------------------------------------------------------------------
            */
            // Cabecera mensual a la que pertenece este día.
            $table->foreignIdFor(MonthlyAttendance::class)
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | ESTADO DEL DÍA
            |--------------------------------------------------------------------------
            |
            | Estado obtenido desde catalogs.
            |
            | Tipo esperado:
            | ATTENDANCE_DAY_STATUS
            |
            | Códigos esperados:
            | UNMARKED
            | PRESENT
            | ABSENT
            | EXCHANGE_WORKED
            | REST
            |
            */
            $table->foreignId('status_id')
                ->constrained('catalogs')
                ->restrictOnDelete()
                ->comment('Estado diario de asistencia registrado en catalogs.');

            /*
            |--------------------------------------------------------------------------
            | FECHA DEL CALENDARIO
            |--------------------------------------------------------------------------
            */
            // Fecha exacta del día dentro del calendario mensual.
            $table->date('attendance_date')
                ->comment('Fecha exacta del día marcado.');

            /*
            |--------------------------------------------------------------------------
            | TURNO UTILIZADO PARA EL CÁLCULO
            |--------------------------------------------------------------------------
            |
            | Se guarda el turno usado en ese día para conservar historial.
            | Esto evita que los cálculos antiguos cambien si más adelante
            | se modifica el turno asignado al trabajador.
            |
            | Solo será obligatorio cuando el día esté marcado como:
            | - Asistió
            | - Trabajó como canje
            |
            */
            $table->foreignIdFor(WorkShift::class)
                ->nullable()
                ->constrained()
                ->restrictOnDelete()
                ->comment('Turno usado para calcular horas trabajadas y horas extras.');

            /*
            |--------------------------------------------------------------------------
            | HORAS REALES REGISTRADAS
            |--------------------------------------------------------------------------
            |
            | Estos datos se transcriben desde el cuaderno o registro físico.
            | Más adelante también podrían venir desde un hardware de marcación.
            |
            */
            $table->time('entry_time')
                ->nullable()
                ->comment('Hora real de ingreso del trabajador.');

            $table->time('exit_time')
                ->nullable()
                ->comment('Hora real de salida del trabajador.');

            /*
            |--------------------------------------------------------------------------
            | CÁLCULOS DEL SISTEMA
            |--------------------------------------------------------------------------
            |
            | worked_hours:
            | Total de horas trabajadas calculadas usando entry_time y exit_time.
            |
            | overtime_hours:
            | Horas extras calculadas comparando worked_hours contra las horas
            | diarias esperadas del turno usado.
            |
            */
            $table->decimal('worked_hours', 5, 2)
                ->default(0)
                ->comment('Horas trabajadas calculadas automáticamente.');

            $table->decimal('overtime_hours', 5, 2)
                ->default(0)
                ->comment('Horas extras calculadas automáticamente.');

            /*
            |--------------------------------------------------------------------------
            | OBSERVACIÓN
            |--------------------------------------------------------------------------
            */
            $table->string('observation', 255)
                ->nullable()
                ->comment('Observación específica del día.');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | RESTRICCIONES E ÍNDICES
            |--------------------------------------------------------------------------
            */
            // Evita duplicar el mismo día dentro de una asistencia mensual.
            $table->unique(
                ['monthly_attendance_id', 'attendance_date'],
                'attendance_day_month_date_unique'
            );
            // Índices para consultas frecuentes.
            $table->index('status_id');
            $table->index('work_shift_id');
            $table->index('attendance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
