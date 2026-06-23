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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();

            /**
             * Nombre comercial del banco.
             *
             * Ejemplo:
             * - BCP
             * - BBVA
             * - Interbank
             * - Scotiabank
             */
            $table->string('name')->index();

            /**
             * Código interno o SBS.
             */
            $table->string('code')
                ->nullable()
                ->unique();

            /**
             * Estado del registro.
             */
            $table->boolean('status')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
