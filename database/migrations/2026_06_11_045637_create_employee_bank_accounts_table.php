<?php

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
        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELACIONES
            |--------------------------------------------------------------------------
            */

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('bank_id')
                ->constrained()
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | TIPO DE CUENTA
            |--------------------------------------------------------------------------
            |
            | AHORROS
            | CORRIENTE
            | CTS
            |
            */

            $table->foreignId('account_type_id')
                ->constrained('catalogs')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | DATOS BANCARIOS
            |--------------------------------------------------------------------------
            */

            $table->string('account_number', 50);

            $table->string('cci', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | CONTROL
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_primary')->default(false);

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | ÍNDICES
            |--------------------------------------------------------------------------
            */

            $table->index('employee_id');
            $table->index('bank_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
    }
};
