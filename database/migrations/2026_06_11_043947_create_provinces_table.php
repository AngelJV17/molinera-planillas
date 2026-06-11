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
        Schema::create('provinces', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | CLAVE PRIMARIA
            |--------------------------------------------------------------------------
            | Código INEI de provincia.
            |
            | Ejemplo:
            | 1501 = Lima
            | 0801 = Cusco
            */
            $table->unsignedInteger('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | RELACIÓN
            |--------------------------------------------------------------------------
            */
            $table->unsignedSmallInteger('department_id');

            /*
            |--------------------------------------------------------------------------
            | DATOS GENERALES
            |--------------------------------------------------------------------------
            */
            $table->string('name', 100);

            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
