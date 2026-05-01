<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the unique constraint that prevented two schedule slots from sharing
     * the same room at the same date/time. Conflict enforcement now lives in
     * application logic (merge-aware validation with aggregated capacity checks).
     *
     * MySQL uses the composite unique index as the backing index for the room_id
     * foreign key (it starts with room_id). Dropping it directly fails with
     * "needed in a foreign key constraint". The fix is to create a plain index on
     * room_id first (in a separate DDL statement), giving MySQL an alternative
     * backing index before the unique one is removed.
     */
    public function up(): void
    {
        // Step 1 — give MySQL an alternative backing index for the room_id FK.
        Schema::table('section_exam_schedule_slots', function (Blueprint $table) {
            $table->index('room_id', 'section_exam_slots_room_id_idx');
        });

        // Step 2 — now safe to drop the composite unique index.
        Schema::table('section_exam_schedule_slots', function (Blueprint $table) {
            $table->dropUnique('section_exam_slots_room_unique');
        });
    }

    public function down(): void
    {
        // Restore the unique index first so it backs the FK again.
        Schema::table('section_exam_schedule_slots', function (Blueprint $table) {
            $table->unique(['room_id', 'slot_date', 'start_time', 'end_time'], 'section_exam_slots_room_unique');
        });

        // Then remove the plain index that is no longer needed.
        Schema::table('section_exam_schedule_slots', function (Blueprint $table) {
            $table->dropIndex('section_exam_slots_room_id_idx');
        });
    }
};
