<?php

use App\Models\Employee;
use App\Models\User;
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
        Schema::create('monthly_attendances', function (Blueprint $table) {
            $table->id();

            // Trabajador al que pertenece la asistencia mensual.
            $table->foreignIdFor(Employee::class)
                ->constrained()
                ->restrictOnDelete();

            // Estado de la asistencia mensual.
            // Ejemplo en catalogs:
            // type: ATTENDANCE_MONTHLY_STATUS
            // codes: draft, closed
            $table->foreignId('status_id')
                ->constrained('catalogs')
                ->restrictOnDelete()
                ->comment('Estado de la asistencia mensual registrado en catalogs.');

            // Mes y año del control de asistencia.
            $table->unsignedTinyInteger('month')
                ->comment('Mes de la asistencia, del 1 al 12.');

            $table->unsignedSmallInteger('year')
                ->comment('Año de la asistencia.');

            // Días asistidos normalmente.
            $table->unsignedTinyInteger('worked_days')
                ->default(0)
                ->comment('Días asistidos normalmente.');

            // Total de faltas registradas en el mes.
            $table->unsignedTinyInteger('absence_days')
                ->default(0)
                ->comment('Total de faltas registradas.');

            // Faltas que fueron compensadas mediante canje.
            $table->unsignedTinyInteger('compensated_absence_days')
                ->default(0)
                ->comment('Faltas que fueron compensadas por otro día trabajado.');

            // Faltas que no fueron canjeadas.
            // Estas serán consideradas para descuento en planilla.
            $table->unsignedTinyInteger('uncompensated_absence_days')
                ->default(0)
                ->comment('Faltas no canjeadas, consideradas para descuento.');

            // Días trabajados como canje.
            $table->unsignedTinyInteger('exchange_days')
                ->default(0)
                ->comment('Días trabajados para compensar faltas.');

            // Días de descanso o no laborables.
            $table->unsignedTinyInteger('rest_days')
                ->default(0)
                ->comment('Días marcados como descanso o no laborables.');

            // Total de horas extras acumuladas en el mes.
            $table->decimal('overtime_hours', 6, 2)
                ->default(0)
                ->comment('Total de horas extras acumuladas en el mes.');

            // Observación general del mes.
            $table->text('observations')
                ->nullable()
                ->comment('Observaciones generales de la asistencia mensual.');

            // Usuario que creó el registro mensual.
            $table->foreignIdFor(User::class, 'created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Usuario que cerró la asistencia mensual.
            $table->foreignIdFor(User::class, 'closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Fecha de cierre de la asistencia mensual.
            $table->timestamp('closed_at')
                ->nullable()
                ->comment('Fecha y hora en que se cerró la asistencia mensual.');

            $table->timestamps();

            // Evita duplicar la asistencia del mismo trabajador en el mismo mes y año.
            $table->unique(
                ['employee_id', 'month', 'year'],
                'monthly_attendance_employee_month_year_unique'
            );

            // Índices para filtros frecuentes.
            $table->index('status_id');
            $table->index(['month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_attendances');
    }
};
