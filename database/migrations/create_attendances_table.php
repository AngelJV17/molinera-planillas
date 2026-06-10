<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date');

            $table->string('attendance_type', 80)->comment('Catalog type=ATTENDANCE_TYPE');

            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->text('observations')->nullable();

            $table->timestamps();

            $table->unique(['worker_id', 'attendance_date']);
            $table->index('attendance_date');
            $table->index('attendance_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};