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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | IDENTIFICACIÓN
            |--------------------------------------------------------------------------
            */

            // Código interno del trabajador
            $table->string('employee_code', 20)->unique();

            // DNI, CE, PASAPORTE, etc.
            $table->foreignId('document_type_id')
                ->constrained('catalogs')
                ->restrictOnDelete();

            $table->string('document_number', 20)->unique();

            /*
            |--------------------------------------------------------------------------
            | DATOS PERSONALES
            |--------------------------------------------------------------------------
            */

            $table->string('first_name', 100);

            $table->string('last_name', 100);

            $table->date('birth_date')->nullable();

            $table->foreignId('gender_id')
                ->nullable()
                ->constrained('catalogs')
                ->nullOnDelete();

            $table->foreignId('marital_status_id')
                ->nullable()
                ->constrained('catalogs')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | CONTACTO
            |--------------------------------------------------------------------------
            */

            $table->string('phone', 20)->nullable();

            $table->string('email')->nullable();

            $table->string('address')->nullable();

            /*
            |--------------------------------------------------------------------------
            | UBIGEO
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('district_id')->nullable();

            $table->foreign('district_id')
                ->references('id')
                ->on('districts')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | DATOS LABORALES
            |--------------------------------------------------------------------------
            */

            $table->date('hire_date');

            $table->date('termination_date')->nullable();

            $table->foreignId('position_id')
                ->nullable()
                ->constrained('catalogs')
                ->nullOnDelete();

            $table->foreignId('work_area_id')
                ->nullable()
                ->constrained('catalogs')
                ->nullOnDelete();

            $table->foreignId('work_shift_id')
                ->nullable()
                ->constrained('work_shifts')
                ->nullOnDelete();

            $table->foreignId('employment_status_id')
                ->nullable()
                ->constrained('catalogs')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | REMUNERACIÓN
            |--------------------------------------------------------------------------
            */

            $table->decimal('base_salary', 10, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | SISTEMA PENSIONARIO
            |--------------------------------------------------------------------------
            */

            $table->foreignId('pension_system_id')
                ->nullable()
                ->constrained('catalogs')
                ->nullOnDelete();

            // Código AFP
            $table->string('cuspp', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | ARCHIVOS
            |--------------------------------------------------------------------------
            */

            $table->string('photo')->nullable();

            $table->string('signature')->nullable();

            /*
            |--------------------------------------------------------------------------
            | ESTADO
            |--------------------------------------------------------------------------
            */

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
