<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_exam_schedule_slot_proctors', function (Blueprint $table) {
            $table->unsignedBigInteger('section_exam_schedule_slot_id');
            $table->unsignedBigInteger('proctor_id');
            $table->timestamps();

            $table->primary(['section_exam_schedule_slot_id', 'proctor_id'], 'section_exam_slot_proctors_primary');
            $table->index(['proctor_id'], 'sched_slot_proctors_proctor_idx');

            $table->foreign('section_exam_schedule_slot_id', 'sched_slot_proctors_slot_fk')
                ->references('id')
                ->on('section_exam_schedule_slots')
                ->cascadeOnDelete();

            $table->foreign('proctor_id', 'sched_slot_proctors_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_exam_schedule_slot_proctors');
    }
};
