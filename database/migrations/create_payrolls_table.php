<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->smallInteger('period_year');
            $table->tinyInteger('period_month');

            $table->string('status', 80)->default('DRAFT')->comment('Catalog type=PAYROLL_STATUS');

            $table->decimal('base_salary', 10, 2)->default(0);
            $table->decimal('bonuses', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            // net_salary se calcula en el modelo o en una consulta, no como columna generada
            // para mayor portabilidad entre motores de BBDD.

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['worker_id', 'period_year', 'period_month']);
            $table->index('status');
            $table->index(['period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};