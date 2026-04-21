<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_exam_schedule_slot_id');
            $table->unsignedBigInteger('student_profile_id');
            $table->unsignedBigInteger('exam_permit_id')->nullable();
            $table->unsignedBigInteger('logged_by')->nullable();
            $table->timestamp('logged_at')->nullable();
            $table->timestamps();

            $table->foreign('section_exam_schedule_slot_id', 'exam_attendance_slot_fk')
                ->references('id')
                ->on('section_exam_schedule_slots')
                ->cascadeOnDelete();
            $table->foreign('student_profile_id', 'exam_attendance_student_fk')
                ->references('id')
                ->on('student_profiles')
                ->cascadeOnDelete();
            $table->foreign('exam_permit_id', 'exam_attendance_permit_fk')
                ->references('id')
                ->on('exam_permits')
                ->nullOnDelete();
            $table->foreign('logged_by', 'exam_attendance_logged_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['section_exam_schedule_slot_id', 'student_profile_id'], 'exam_attendance_slot_student_unique');
            $table->index(['student_profile_id', 'logged_at'], 'exam_attendance_student_logged_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attendances');
    }
};
