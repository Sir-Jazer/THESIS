<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_matrix_slots') || ! Schema::hasTable('exam_matrix_slot_subjects')) {
            return;
        }

        if (Schema::hasColumn('exam_matrix_slots', 'subject_id')) {
            DB::table('exam_matrix_slots')
                ->whereNotNull('subject_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    $payload = [];
                    $timestamp = now();

                    foreach ($rows as $row) {
                        $payload[] = [
                            'exam_matrix_slot_id' => $row->id,
                            'subject_id' => (int) $row->subject_id,
                            'sort_order' => 0,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('exam_matrix_slot_subjects')->insertOrIgnore($payload);
                    }
                });

            Schema::table('exam_matrix_slots', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subject_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('exam_matrix_slots')) {
            return;
        }

        if (! Schema::hasColumn('exam_matrix_slots', 'subject_id')) {
            Schema::table('exam_matrix_slots', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->after('is_fixed')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('exam_matrix_slot_subjects')) {
            return;
        }

        $firstSubjectPerSlot = DB::table('exam_matrix_slot_subjects')
            ->select('exam_matrix_slot_id', 'subject_id')
            ->orderBy('exam_matrix_slot_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->unique('exam_matrix_slot_id');

        foreach ($firstSubjectPerSlot as $row) {
            DB::table('exam_matrix_slots')
                ->where('id', $row->exam_matrix_slot_id)
                ->update(['subject_id' => $row->subject_id]);
        }
    }
};
