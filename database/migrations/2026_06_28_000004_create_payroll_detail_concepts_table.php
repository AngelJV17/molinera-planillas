<?php

use App\Models\PayrollDetail;
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
        Schema::create('payroll_detail_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PayrollDetail::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('concept_type_id')
                ->constrained('catalogs')
                ->restrictOnDelete();
            $table->string('code', 80);
            $table->string('name', 160);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('rate', 12, 4)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('taxable')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['payroll_detail_id', 'concept_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_detail_concepts');
    }
};
