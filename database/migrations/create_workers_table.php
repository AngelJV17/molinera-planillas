<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            // Campos de catálogo: guardan el 'code', no el id.
            // No se pone foreignId porque el catálogo no tiene relación directa por PK.
            $table->string('document_type', 80)->comment('Catalog type=DOCUMENT_TYPE');
            $table->string('document_number', 20);
            $table->string('status', 80)->default('ACTIVE')->comment('Catalog type=WORKER_STATUS');
            $table->string('position', 80)->comment('Catalog type=POSITION');
            $table->string('department', 80)->comment('Catalog type=DEPARTMENT');

            $table->date('hire_date');
            $table->decimal('base_salary', 10, 2)->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['document_type', 'document_number']);
            $table->index('status');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};