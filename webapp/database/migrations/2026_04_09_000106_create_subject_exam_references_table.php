<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_exam_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->unsignedTinyInteger('semester');
            $table->string('exam_period', 20);
            $table->string('exam_reference_number', 50);
            $table->timestamps();

            $table->unique(['subject_id', 'academic_year', 'semester', 'exam_period'], 'subject_exam_refs_subject_scope_unique');
            $table->unique(['academic_year', 'semester', 'exam_period', 'exam_reference_number'], 'subject_exam_refs_value_scope_unique');
            $table->index(['academic_year', 'semester', 'exam_period'], 'subject_exam_refs_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_exam_references');
    }
};
