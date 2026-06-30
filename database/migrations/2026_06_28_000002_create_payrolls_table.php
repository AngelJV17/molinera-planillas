<?php

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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('status_id')
                ->constrained('catalogs')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('payment_date')->nullable();
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('total_base_salary', 12, 2)->default(0);
            $table->decimal('total_income', 12, 2)->default(0);
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->decimal('total_employer_contribution', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->text('observations')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignIdFor(User::class, 'generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignIdFor(User::class, 'reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignIdFor(User::class, 'paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['month', 'year'], 'payroll_month_year_unique');
            $table->index('status_id');
            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
