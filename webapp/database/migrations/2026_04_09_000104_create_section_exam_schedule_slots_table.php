<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_exam_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_exam_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_matrix_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_fixed')->default(false);
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_manual_assignment')->default(false);
            $table->timestamps();

            $table->unique(['section_exam_schedule_id', 'slot_date', 'start_time', 'end_time'], 'section_exam_slots_schedule_unique');
            $table->unique(['room_id', 'slot_date', 'start_time', 'end_time'], 'section_exam_slots_room_unique');
            $table->index(['slot_date', 'start_time', 'end_time']);
            $table->index(['subject_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_exam_schedule_slots');
    }
};
