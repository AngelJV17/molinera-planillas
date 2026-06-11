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
        Schema::create('departments', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | CLAVE PRIMARIA
            |--------------------------------------------------------------------------
            | Código INEI del departamento.
            | Ejemplo:
            | 15 = Lima
            | 08 = Cusco
            */
            $table->unsignedSmallInteger('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | DATOS GENERALES
            |--------------------------------------------------------------------------
            */
            $table->string('name', 100);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
