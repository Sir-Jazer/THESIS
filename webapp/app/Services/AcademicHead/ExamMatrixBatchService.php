<?php

namespace App\Services\AcademicHead;

use App\Models\ExamMatrix;
use App\Models\ExamMatrixSubjectBatch;
use App\Models\ExamMatrixSubjectBatchSectionAssignment;
use App\Models\Section;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamMatrixBatchService
{
    /**
     * @return array{has_duplicates: bool, counts: array<int, int>}
     */
    public function syncDuplicateSubjectBatches(ExamMatrix $matrix): array
    {
        $matrix->loadMissing(['slots.slotSubjects.subject']);

        $duplicates = $this->duplicateSubjectSlots($matrix);
        $counts = [];
        $timestamp = now();

        $upsertRows = [];
        foreach ($duplicates as $subjectId => $slots) {
            $counts[(int) $subjectId] = $slots->count();

            foreach ($slots->values() as $index => $slot) {
                $upsertRows[] = [
                    'exam_matrix_id' => $matrix->id,
                    'subject_id' => (int) $subjectId,
                    'batch_no' => $index + 1,
                    'exam_matrix_slot_id' => (int) $slot->id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::transaction(function () use ($matrix, $upsertRows, $counts): void {
            if ($upsertRows !== []) {
                DB::table('exam_matrix_subject_batches')->upsert(
                    $upsertRows,
                    ['exam_matrix_id', 'subject_id', 'batch_no'],
                    ['exam_matrix_slot_id', 'updated_at']
                );
            }

            $subjectIds = array_map('intval', array_keys($counts));

            if ($subjectIds === []) {
                ExamMatrixSubjectBatch::query()
                    ->where('exam_matrix_id', $matrix->id)
                    ->delete();

                ExamMatrixSubjectBatchSectionAssignment::query()
                    ->where('exam_matrix_id', $matrix->id)
                    ->delete();

                return;
            }

            ExamMatrixSubjectBatch::query()
                ->where('exam_matrix_id', $matrix->id)
                ->whereNotIn('subject_id', $subjectIds)
                ->delete();

            ExamMatrixSubjectBatchSectionAssignment::query()
                ->where('exam_matrix_id', $matrix->id)
                ->whereNotIn('subject_id', $subjectIds)
                ->delete();

            foreach ($counts as $subjectId => $slotCount) {
                ExamMatrixSubjectBatch::query()
                    ->where('exam_matrix_id', $matrix->id)
                    ->where('subject_id', (int) $subjectId)
                    ->where('batch_no', '>', (int) $slotCount)
                    ->delete();

                ExamMatrixSubjectBatchSectionAssignment::query()
                    ->where('exam_matrix_id', $matrix->id)
                    ->where('subject_id', (int) $subjectId)
                    ->where('batch_no', '>', (int) $slotCount)
                    ->delete();
            }
        });

        return [
            'has_duplicates' => $duplicates->isNotEmpty(),
            'counts' => $counts,
        ];
    }

    /**
     * @return array{
     *     has_duplicates: bool,
     *     is_complete: bool,
     *     subjects: array<int, array{
     *         subject_id: int,
     *         subject_label: string,
     *         batches: array<int, array{batch_no: int, slot_label: string}>,
     *         sections: array<int, array{id: int, section_code: string, program_id: int, program_code: string, year_level: int}>,
     *         assignments: array<int, int>,
     *         is_complete: bool,
     *         missing_count: int
     *     }>
     * }
     */
    public function buildClassificationData(ExamMatrix $matrix): array
    {
        $this->syncDuplicateSubjectBatches($matrix);

        $batchRows = ExamMatrixSubjectBatch::query()
            ->with(['subject:id,code,course_serial_number,name', 'matrixSlot'])
            ->where('exam_matrix_id', $matrix->id)
            ->orderBy('subject_id')
            ->orderBy('batch_no')
            ->get()
            ->groupBy('subject_id');

        if ($batchRows->isEmpty()) {
            return [
                'has_duplicates' => false,
                'is_complete' => true,
                'subjects' => [],
            ];
        }

        $assignmentRows = ExamMatrixSubjectBatchSectionAssignment::query()
            ->where('exam_matrix_id', $matrix->id)
            ->get()
            ->groupBy('subject_id')
            ->map(fn (Collection $rows): Collection => $rows->keyBy('section_id'));

        $subjects = [];

        foreach ($batchRows as $subjectId => $rows) {
            $subjectId = (int) $subjectId;
            $subject = $rows->first()?->subject;

            $sections = $this->sectionsForSubject($matrix, $subjectId);
            $validBatchNos = $rows->pluck('batch_no')->map(fn ($value) => (int) $value)->values()->all();

            $subjectAssignments = $assignmentRows->get($subjectId, collect());
            $assignmentMap = [];
            foreach ($subjectAssignments as $sectionId => $assignment) {
                $assignmentMap[(int) $sectionId] = (int) $assignment->batch_no;
            }

            $missingCount = 0;
            foreach ($sections as $section) {
                $batchNo = $assignmentMap[(int) $section['id']] ?? null;
                if ($batchNo === null || ! in_array((int) $batchNo, $validBatchNos, true)) {
                    $missingCount++;
                }
            }

            $subjects[] = [
                'subject_id' => $subjectId,
                'subject_label' => $subject
                    ? trim($subject->code.' | '.($subject->course_serial_number ?: 'No Serial').' - '.$subject->name)
                    : 'Subject #'.$subjectId,
                'batches' => $rows->map(function (ExamMatrixSubjectBatch $row): array {
                    $slot = $row->matrixSlot;
                    $dateValue = $slot?->slot_date;
                    $dateLabel = $dateValue instanceof \DateTimeInterface
                        ? $dateValue->format('Y-m-d')
                        : (string) ($dateValue ?? 'Unknown Date');
                    $start = $this->formatTime((string) ($slot?->start_time ?? ''));
                    $end = $this->formatTime((string) ($slot?->end_time ?? ''));

                    return [
                        'batch_no' => (int) $row->batch_no,
                        'slot_label' => trim($dateLabel.' '.$start.'-'.$end),
                    ];
                })->values()->all(),
                'sections' => $sections->values()->all(),
                'assignments' => $assignmentMap,
                'is_complete' => $missingCount === 0,
                'missing_count' => $missingCount,
            ];
        }

        return [
            'has_duplicates' => true,
            'is_complete' => collect($subjects)->every(fn (array $subject): bool => (bool) $subject['is_complete']),
            'subjects' => $subjects,
        ];
    }

    /**
     * @param array<string, mixed> $assignments
     */
    public function persistAssignments(ExamMatrix $matrix, array $assignments): void
    {
        $classificationData = $this->buildClassificationData($matrix);

        if (! $classificationData['has_duplicates']) {
            return;
        }

        $rows = [];
        $timestamp = now();

        foreach ($classificationData['subjects'] as $subjectData) {
            $subjectId = (int) $subjectData['subject_id'];
            $validBatchNos = collect($subjectData['batches'])
                ->pluck('batch_no')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all();

            foreach ($subjectData['sections'] as $section) {
                $sectionId = (int) $section['id'];
                $batchNo = Arr::get($assignments, $subjectId.'.'.$sectionId);
                $batchNo = $batchNo === null || $batchNo === '' ? null : (int) $batchNo;

                if ($batchNo === null || ! in_array($batchNo, $validBatchNos, true)) {
                    throw ValidationException::withMessages([
                        'assignments.'.$subjectId.'.'.$sectionId => 'Select a valid batch for '.$section['program_code'].' '.$section['section_code'].'.',
                    ]);
                }

                $rows[] = [
                    'exam_matrix_id' => $matrix->id,
                    'subject_id' => $subjectId,
                    'program_id' => (int) $section['program_id'],
                    'year_level' => (int) $section['year_level'],
                    'section_id' => $sectionId,
                    'batch_no' => $batchNo,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::transaction(function () use ($matrix, $rows, $classificationData): void {
            $subjectIds = collect($classificationData['subjects'])
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            ExamMatrixSubjectBatchSectionAssignment::query()
                ->where('exam_matrix_id', $matrix->id)
                ->whereIn('subject_id', $subjectIds)
                ->delete();

            if ($rows !== []) {
                DB::table('exam_matrix_subject_batch_section_assignments')->insert($rows);
            }
        });
    }

    /**
     * @return array{has_duplicates: bool, is_complete: bool, unresolved_subject_labels: array<int, string>}
     */
    public function getUploadBlockers(ExamMatrix $matrix): array
    {
        $classificationData = $this->buildClassificationData($matrix);

        $unresolved = collect($classificationData['subjects'])
            ->filter(fn (array $subject): bool => ! (bool) $subject['is_complete'])
            ->map(function (array $subject): string {
                return $subject['subject_label'].' ('.$subject['missing_count'].' section assignment(s) missing)';
            })
            ->values()
            ->all();

        return [
            'has_duplicates' => (bool) $classificationData['has_duplicates'],
            'is_complete' => (bool) $classificationData['is_complete'],
            'unresolved_subject_labels' => $unresolved,
        ];
    }

    /**
     * @return array<int, int>
     */
    public function sectionBatchMap(ExamMatrix $matrix, Section $section): array
    {
        return ExamMatrixSubjectBatchSectionAssignment::query()
            ->where('exam_matrix_id', $matrix->id)
            ->where('section_id', $section->id)
            ->pluck('batch_no', 'subject_id')
            ->mapWithKeys(fn ($batchNo, $subjectId): array => [(int) $subjectId => (int) $batchNo])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function slotBatchMap(ExamMatrix $matrix): array
    {
        return ExamMatrixSubjectBatch::query()
            ->where('exam_matrix_id', $matrix->id)
            ->get()
            ->mapWithKeys(fn (ExamMatrixSubjectBatch $batch): array => [
                $this->subjectSlotKey((int) $batch->subject_id, (int) $batch->exam_matrix_slot_id) => (int) $batch->batch_no,
            ])
            ->all();
    }

    public function subjectSlotKey(int $subjectId, int $slotId): string
    {
        return $subjectId.':'.$slotId;
    }

    /**
     * @return Collection<int, Collection<int, \App\Models\ExamMatrixSlot>>
     */
    private function duplicateSubjectSlots(ExamMatrix $matrix): Collection
    {
        $subjectSlotGroups = collect();

        foreach ($matrix->slots as $slot) {
            foreach ($slot->slotSubjects as $slotSubject) {
                $subjectId = (int) $slotSubject->subject_id;

                if (! $subjectSlotGroups->has($subjectId)) {
                    $subjectSlotGroups->put($subjectId, collect());
                }

                $subjectSlotGroups->get($subjectId)->put((int) $slot->id, $slot);
            }
        }

        return $subjectSlotGroups
            ->filter(fn (Collection $slots): bool => $slots->count() > 1)
            ->map(function (Collection $slots): Collection {
                return $slots
                    ->values()
                    ->sortBy(function ($slot): string {
                        $sortOrder = str_pad((string) ((int) ($slot->sort_order ?? 0)), 5, '0', STR_PAD_LEFT);
                        $date = (string) $slot->slot_date;
                        $start = (string) $slot->start_time;
                        $id = str_pad((string) ((int) $slot->id), 10, '0', STR_PAD_LEFT);

                        return $sortOrder.'|'.$date.'|'.$start.'|'.$id;
                    })
                    ->values();
            });
    }

    /**
     * @return Collection<int, array{id: int, section_code: string, program_id: int, program_code: string, year_level: int}>
     */
    private function sectionsForSubject(ExamMatrix $matrix, int $subjectId): Collection
    {
        return Section::query()
            ->select([
                'sections.id',
                'sections.section_code',
                'sections.program_id',
                'sections.year_level',
                'programs.code as program_code',
            ])
            ->join('program_subjects', function ($join) use ($subjectId, $matrix): void {
                $join->on('program_subjects.program_id', '=', 'sections.program_id')
                    ->on('program_subjects.year_level', '=', 'sections.year_level')
                    ->where('program_subjects.subject_id', '=', $subjectId)
                    ->where('program_subjects.semester', '=', (int) $matrix->semester);
            })
            ->join('programs', 'programs.id', '=', 'sections.program_id')
            ->orderBy('programs.code')
            ->orderBy('sections.year_level')
            ->orderBy('sections.section_code')
            ->get()
            ->map(function (Section $section): array {
                return [
                    'id' => (int) $section->id,
                    'section_code' => (string) $section->section_code,
                    'program_id' => (int) $section->program_id,
                    'program_code' => (string) ($section->program_code ?? ''),
                    'year_level' => (int) $section->year_level,
                ];
            })
            ->unique('id')
            ->values();
    }

    private function formatTime(string $time): string
    {
        if (preg_match('/^\d{2}:\d{2}/', $time) === 1) {
            return substr($time, 0, 5);
        }

        return $time;
    }
}
