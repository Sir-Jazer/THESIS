<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_permits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_profile_id');
            $table->string('academic_year', 20);
            $table->unsignedTinyInteger('semester');
            $table->string('exam_period', 30);
            $table->string('qr_token', 191);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('student_profile_id', 'exam_permits_student_fk')
                ->references('id')
                ->on('student_profiles')
                ->cascadeOnDelete();
            $table->foreign('generated_by', 'exam_permits_generated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['student_profile_id', 'academic_year', 'semester', 'exam_period'], 'exam_permits_context_unique');
            $table->unique('qr_token', 'exam_permits_qr_token_unique');
            $table->index(['academic_year', 'semester', 'exam_period', 'is_active'], 'exam_permits_period_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_permits');
    }
};
