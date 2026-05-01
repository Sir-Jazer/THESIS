<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_matrix_subject_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_matrix_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('batch_no');
            $table->foreignId('exam_matrix_slot_id')->constrained('exam_matrix_slots')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_matrix_id', 'subject_id', 'batch_no'], 'matrix_subject_batch_unique');
            $table->unique(['exam_matrix_slot_id', 'subject_id'], 'matrix_slot_subject_batch_unique');
            $table->index(['exam_matrix_id', 'subject_id'], 'matrix_subject_batch_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_matrix_subject_batches');
    }
};
