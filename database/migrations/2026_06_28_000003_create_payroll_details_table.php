<?php

use App\Models\Employee;
use App\Models\MonthlyAttendance;
use App\Models\Payroll;
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
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Payroll::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)
                ->constrained()
                ->restrictOnDelete();
            $table->foreignIdFor(MonthlyAttendance::class)
                ->constrained()
                ->restrictOnDelete();

            // Snapshot del trabajador y la asistencia usada para que la planilla sea historica.
            $table->string('employee_code', 20);
            $table->string('employee_name', 220);
            $table->string('document_number', 20);
            $table->string('pension_system_code', 80)->nullable();
            $table->string('pension_system_name', 120)->nullable();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->unsignedTinyInteger('worked_days')->default(0);
            $table->unsignedTinyInteger('absence_days')->default(0);
            $table->unsignedTinyInteger('uncompensated_absence_days')->default(0);
            $table->unsignedTinyInteger('rest_days')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->decimal('total_income', 12, 2)->default(0);
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->decimal('total_employer_contribution', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id'], 'payroll_detail_employee_unique');
            $table->unique('monthly_attendance_id', 'payroll_detail_attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
