<?php

use App\Models\WorkShift;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_shifts', function (Blueprint $table) {
            $table->boolean('uses_daily_rules')->default(false)->after('daily_hours');
            $table->boolean('rotation_enabled')->default(false)->after('crosses_midnight');
            $table->unsignedTinyInteger('rotation_work_days')->nullable()->after('rotation_enabled');
            $table->unsignedTinyInteger('rotation_rest_days')->nullable()->after('rotation_work_days');
            $table->date('rotation_start_date')->nullable()->after('rotation_rest_days');
        });

        Schema::create('work_shift_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(WorkShift::class)->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('1=Lunes, 7=Domingo');
            $table->boolean('is_working_day')->default(true);
            $table->time('start_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('tolerance_minutes')->default(0);
            $table->decimal('expected_hours', 5, 2)->default(8);
            $table->boolean('crosses_midnight')->default(false);
            $table->boolean('counts_as_full_day')->default(true);
            $table->boolean('overtime_pay_enabled')->default(true);
            $table->decimal('overtime_after_hours', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['work_shift_id', 'day_of_week'], 'work_shift_rule_day_unique');
        });

        Schema::table('attendance_days', function (Blueprint $table) {
            $table->decimal('payable_overtime_hours', 5, 2)
                ->default(0)
                ->after('overtime_hours')
                ->comment('Horas extra que si se remuneran en planilla.');
        });

        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->decimal('payable_overtime_hours', 8, 2)
                ->default(0)
                ->after('overtime_hours')
                ->comment('Total de horas extra remunerables del mes.');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->dropColumn('payable_overtime_hours');
        });

        Schema::table('attendance_days', function (Blueprint $table) {
            $table->dropColumn('payable_overtime_hours');
        });

        Schema::dropIfExists('work_shift_rules');

        Schema::table('work_shifts', function (Blueprint $table) {
            $table->dropColumn([
                'uses_daily_rules',
                'rotation_enabled',
                'rotation_work_days',
                'rotation_rest_days',
                'rotation_start_date',
            ]);
        });
    }
};
