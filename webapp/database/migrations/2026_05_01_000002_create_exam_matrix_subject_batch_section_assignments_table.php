<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_matrix_subject_batch_section_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_matrix_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('batch_no');
            $table->timestamps();

            $table->unique(['exam_matrix_id', 'subject_id', 'section_id'], 'matrix_subject_section_batch_unique');
            $table->index(['exam_matrix_id', 'subject_id', 'program_id', 'year_level', 'batch_no'], 'matrix_subject_scope_batch_idx');
            $table->index(['exam_matrix_id', 'section_id'], 'matrix_section_batch_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_matrix_subject_batch_section_assignments');
    }
};
