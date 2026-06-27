<?php

use App\Models\MonthlyAttendance;
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

            // Cabecera mensual a la que pertenece este día.
            $table->foreignIdFor(MonthlyAttendance::class)
                ->constrained()
                ->cascadeOnDelete();

            // Estado del día.
            // Ejemplo en catalogs:
            // type: ATTENDANCE_DAY_STATUS
            // codes: unmarked, present, absent, exchange_worked, rest
            $table->foreignId('status_id')
                ->constrained('catalogs')
                ->restrictOnDelete()
                ->comment('Estado diario de asistencia registrado en catalogs.');

            // Fecha exacta del día en el calendario.
            $table->date('attendance_date')
                ->comment('Fecha exacta del día marcado.');

            // Horas extras registradas en ese día.
            $table->decimal('overtime_hours', 5, 2)
                ->default(0)
                ->comment('Horas extras registradas en este día.');

            // Observación específica del día.
            $table->string('observation', 255)
                ->nullable()
                ->comment('Observación específica del día.');

            $table->timestamps();

            // Evita duplicar el mismo día dentro de una asistencia mensual.
            $table->unique(
                ['monthly_attendance_id', 'attendance_date'],
                'attendance_day_month_date_unique'
            );

            // Índices para consultas del calendario.
            $table->index('status_id');
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
