<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique('payroll_month_year_unique');
            $table->foreignId('payroll_group_id')
                ->nullable()
                ->after('status_id')
                ->constrained('catalogs')
                ->restrictOnDelete();
            $table->unique(['month', 'year', 'payroll_group_id'], 'payroll_period_group_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique('payroll_period_group_unique');
            $table->dropConstrainedForeignId('payroll_group_id');
            $table->unique(['month', 'year'], 'payroll_month_year_unique');
        });
    }
};
