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
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | INFORMACIÓN GENERAL
            |--------------------------------------------------------------------------
            */
            $table->string('name', 100);

            $table->string('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | HORARIO
            |--------------------------------------------------------------------------
            */
            $table->time('start_time');

            $table->time('end_time');

            /*
            |--------------------------------------------------------------------------
            | TOLERANCIA
            |--------------------------------------------------------------------------
            | Minutos permitidos antes de registrar tardanza.
            */
            $table->unsignedInteger('grace_minutes')->default(0);

            /*
            |--------------------------------------------------------------------------
            | HORAS DE JORNADA
            |--------------------------------------------------------------------------
            */
            $table->decimal('daily_hours', 5, 2)->default(8);

            /*
            |--------------------------------------------------------------------------
            | CONTROL
            |--------------------------------------------------------------------------
            | Indica si el turno termina al día siguiente.
            */
            $table->boolean('cross_midnight')->default(false);

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
        Schema::dropIfExists('work_shifts');
    }
};
