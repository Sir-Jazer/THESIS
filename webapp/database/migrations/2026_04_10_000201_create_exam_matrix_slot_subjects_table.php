<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_matrix_slot_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_matrix_slot_id')->constrained('exam_matrix_slots')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['exam_matrix_slot_id', 'subject_id'], 'exam_matrix_slot_subject_unique');
            $table->index(['exam_matrix_slot_id', 'sort_order'], 'exam_matrix_slot_subject_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_matrix_slot_subjects');
    }
};
