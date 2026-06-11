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
        Schema::create('districts', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | CLAVE PRIMARIA
            |--------------------------------------------------------------------------
            | Código INEI del distrito.
            |
            | Ejemplo:
            | 150132 = San Juan de Lurigancho
            */
            $table->unsignedInteger('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | RELACIÓN
            |--------------------------------------------------------------------------
            */
            $table->unsignedInteger('province_id');

            /*
            |--------------------------------------------------------------------------
            | DATOS GENERALES
            |--------------------------------------------------------------------------
            */
            $table->string('name', 100);

            $table->timestamps();

            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('province_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
