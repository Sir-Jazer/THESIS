<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_matrix_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->unsignedTinyInteger('semester');
            $table->string('exam_period', 30);
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['section_id', 'academic_year', 'semester', 'exam_period'], 'section_exam_schedule_context_unique');
            $table->index(['program_id', 'academic_year', 'semester', 'exam_period', 'status'], 'section_exam_schedules_publish_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_exam_schedules');
    }
};
