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
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();

            // Agrupa las opciones por tipo.
            // Ejemplo: DOCUMENT_TYPE, WORKER_STATUS, ATTENDANCE_STATUS.
            $table->string('type', 80);

            // Código interno usado por el sistema.
            // Ejemplo: DNI, ACTIVE, PRESENT, ABSENT.
            $table->string('code', 80);

            // Nombre visible para el usuario.
            // Ejemplo: Documento Nacional de Identidad, Activo, Asistió.
            $table->string('name', 120);

            // Descripción opcional para explicar el uso del catálogo.
            $table->string('description')->nullable();

            // Permite activar o desactivar una opción sin eliminarla.
            $table->boolean('status')->default(true);

            $table->timestamps();

            // Evita duplicar el mismo código dentro del mismo tipo.
            $table->unique(['type', 'code']);

            // Índice para acelerar búsquedas por tipo.
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogs');
    }
};
