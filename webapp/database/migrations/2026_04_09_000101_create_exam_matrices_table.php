<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->unsignedTinyInteger('semester');
            $table->string('exam_period', 30);
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_year', 'semester', 'exam_period', 'program_id'], 'exam_matrices_context_unique');
            $table->index(['program_id', 'exam_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_matrices');
    }
};
