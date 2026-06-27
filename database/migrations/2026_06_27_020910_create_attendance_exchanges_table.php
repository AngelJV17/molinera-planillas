<?php

use App\Models\AttendanceDay;
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
        Schema::create('attendance_exchanges', function (Blueprint $table) {
            $table->id();

            // Trabajador al que pertenece el canje.
            $table->foreignIdFor(Employee::class)
                ->constrained()
                ->restrictOnDelete();

            // Estado del canje.
            // Ejemplo en catalogs:
            // type: ATTENDANCE_EXCHANGE_STATUS
            // codes: pending, applied, cancelled
            $table->foreignId('status_id')
                ->constrained('catalogs')
                ->restrictOnDelete()
                ->comment('Estado del canje registrado en catalogs.');

            // Día donde el trabajador faltó.
            $table->foreignIdFor(AttendanceDay::class, 'absence_attendance_day_id')
                ->constrained('attendance_days')
                ->cascadeOnDelete();

            // Día donde el trabajador trabajó para compensar la falta.
            $table->foreignIdFor(AttendanceDay::class, 'exchange_attendance_day_id')
                ->constrained('attendance_days')
                ->cascadeOnDelete();

            // Fecha de la falta.
            // Se guarda también aquí para facilitar reportes y búsquedas.
            $table->date('absence_date')
                ->comment('Fecha de la falta que será compensada.');

            // Fecha trabajada para compensar la falta.
            // Puede ser cualquier otro día válido según las reglas del negocio.
            $table->date('exchange_date')
                ->comment('Fecha trabajada para compensar la falta.');

            // Observación del canje.
            $table->string('observation', 255)
                ->nullable()
                ->comment('Observación del canje.');

            // Usuario que registró el canje.
            $table->foreignIdFor(User::class, 'registered_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Una falta solo puede ser compensada una vez.
            $table->unique(
                'absence_attendance_day_id',
                'attendance_exchange_absence_day_unique'
            );

            // Un día trabajado como canje solo debe compensar una falta.
            $table->unique(
                'exchange_attendance_day_id',
                'attendance_exchange_worked_day_unique'
            );

            // Índices para reportes y filtros.
            $table->index('employee_id');
            $table->index('status_id');
            $table->index('absence_date');
            $table->index('exchange_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_exchanges');
    }
};
