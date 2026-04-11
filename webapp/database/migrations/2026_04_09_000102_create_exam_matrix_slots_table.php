<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_matrix_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_matrix_id')->constrained()->cascadeOnDelete();
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_fixed')->default(false);
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['exam_matrix_id', 'slot_date', 'start_time', 'end_time'], 'exam_matrix_slots_unique');
            $table->index(['exam_matrix_id', 'is_fixed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_matrix_slots');
    }
};
