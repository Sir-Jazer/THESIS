<?php

namespace App\Services\AcademicHead;

use App\Models\ExamMatrix;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionExamSchedule;
use App\Models\SectionExamScheduleSlot;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(private readonly ExamMatrixBatchService $batchService)
    {
    }

    /**
     * @return array{
     *     has_blockers: bool,
     *     total_blockers: int,
     *     missing_fixed_subjects: array<int, string>,
     *     assigned_without_room: array<int, string>,
     *     assigned_without_proctor: array<int, string>
     * }
     */
    public function getPublishReadiness(SectionExamSchedule $schedule): array
    {
        $schedule = $this->reconcileDraftMatrixAssignments($schedule);
        $blockers = $this->collectPublishBlockers($schedule);

        $totalBlockers = count($blockers['missing_fixed_subjects'])
            + count($blockers['assigned_without_room'])
            + count($blockers['assigned_without_proctor']);

        return [
            'has_blockers' => $totalBlockers > 0,
            'total_blockers' => $totalBlockers,
            'missing_fixed_subjects' => $blockers['missing_fixed_subjects'],
            'assigned_without_room' => $blockers['assigned_without_room'],
            'assigned_without_proctor' => $blockers['assigned_without_proctor'],
        ];
    }

    public function generateForSection(ExamMatrix $matrix, Section $section, int $actorId): SectionExamSchedule
    {
        $matrix->loadMissing(['slots.slotSubjects']);
        $this->batchService->syncDuplicateSubjectBatches($matrix);
        $slotBatchMap = $this->batchService->slotBatchMap($matrix);
        $sectionBatchMap = $this->batchService->sectionBatchMap($matrix, $section);

        return DB::transaction(function () use ($matrix, $section, $actorId, $slotBatchMap, $sectionBatchMap): SectionExamSchedule {
            $schedule = SectionExamSchedule::updateOrCreate(
                [
                    'section_id' => $section->id,
                    'academic_year' => $matrix->academic_year,
                    'semester' => $matrix->semester,
                    'exam_period' => $matrix->exam_period,
                ],
                [
                    'exam_matrix_id' => $matrix->id,
                    'program_id' => $section->program_id,
                    'status' => 'draft',
                    'published_at' => null,
                    'published_by' => null,
                    'created_by' => $actorId,
                ]
            );

            $schedule->slots()->delete();

            $eligibleSubjectIds = $this->eligibleSubjectIdsForSection($section, (int) $matrix->semester);

            foreach ($matrix->slots as $slot) {
                $fixedSubjectId = null;

                if ($slot->is_fixed) {
                    $fixedSubjectId = $this->resolveFixedSubjectId($slot, $eligibleSubjectIds);
                    $fixedSubjectId = $this->resolveBatchConstrainedSubject(
                        $slot,
                        $fixedSubjectId,
                        $slotBatchMap,
                        $sectionBatchMap
                    );
                }

                $schedule->slots()->create([
                    'exam_matrix_slot_id' => $slot->id,
                    'slot_date' => $slot->slot_date,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'is_fixed' => $slot->is_fixed,
                    'subject_id' => $fixedSubjectId,
                    'is_manual_assignment' => false,
                ]);
            }

            return $schedule->load(['section.program', 'slots.subject', 'slots.room', 'slots.proctors']);
        });
    }

    public function reset(SectionExamSchedule $schedule): SectionExamSchedule
    {
        $schedule->loadMissing(['section', 'matrix.slots.slotSubjects', 'slots.matrixSlot.slotSubjects']);
        $eligibleSubjectIds = $this->eligibleSubjectIdsForSection($schedule->section, (int) $schedule->semester);

        $slotBatchMap = [];
        $sectionBatchMap = [];

        if ($schedule->matrix) {
            $this->batchService->syncDuplicateSubjectBatches($schedule->matrix);
            $slotBatchMap = $this->batchService->slotBatchMap($schedule->matrix);
            $sectionBatchMap = $this->batchService->sectionBatchMap($schedule->matrix, $schedule->section);
        }

        DB::transaction(function () use ($schedule, $eligibleSubjectIds, $slotBatchMap, $sectionBatchMap): void {
            foreach ($schedule->slots as $slot) {
                $slot->proctors()->detach();

                if ($slot->is_fixed) {
                    $subjectId = $this->resolveFixedSubjectId($slot->matrixSlot, $eligibleSubjectIds);
                    $subjectId = $this->resolveBatchConstrainedSubject(
                        $slot->matrixSlot,
                        $subjectId,
                        $slotBatchMap,
                        $sectionBatchMap
                    );

                    $slot->update([
                        'subject_id' => $subjectId,
                        'room_id' => null,
                        'is_manual_assignment' => false,
                    ]);

                    continue;
                }

                $slot->update([
                    'subject_id' => null,
                    'room_id' => null,
                    'is_manual_assignment' => false,
                ]);
            }

            $schedule->update([
                'status' => 'draft',
                'published_at' => null,
                'published_by' => null,
            ]);
        });

        return $schedule->fresh(['section.program', 'slots.subject', 'slots.room', 'slots.proctors']);
    }

    /**
     * Refreshes a draft schedule from the latest uploaded matrix in the same academic context.
     *
     * @return array{schedule: SectionExamSchedule, updated: int, created: int, removed: int}
     */
    public function refreshFromLatestMatrix(SectionExamSchedule $schedule): array
    {
        if ($schedule->isPublished()) {
            throw ValidationException::withMessages([
                'schedule' => 'Published schedules must be reset before fetching matrix updates.',
            ]);
        }

        $schedule->loadMissing(['section', 'slots.proctors']);

        $latestMatrix = ExamMatrix::query()
            ->uploadedForScheduleContext(
                (string) $schedule->academic_year,
                (int) $schedule->semester,
                (string) $schedule->exam_period,
                (int) $schedule->program_id
            )
            ->first();

        if (! $latestMatrix) {
            throw ValidationException::withMessages([
                'matrix' => 'No uploaded General Exam Matrix found for this schedule context.',
            ]);
        }

        $latestMatrix->loadMissing(['slots.slotSubjects']);
        $this->batchService->syncDuplicateSubjectBatches($latestMatrix);
        $slotBatchMap = $this->batchService->slotBatchMap($latestMatrix);
        $sectionBatchMap = $this->batchService->sectionBatchMap($latestMatrix, $schedule->section);
        $eligibleSubjectIds = $this->eligibleSubjectIdsForSection($schedule->section, (int) $schedule->semester);

        return DB::transaction(function () use ($schedule, $latestMatrix, $eligibleSubjectIds, $slotBatchMap, $sectionBatchMap): array {
            $existingSlots = $schedule->slots->keyBy(function (SectionExamScheduleSlot $slot): string {
                return $this->buildSlotKey($slot->slot_date, (string) $slot->start_time, (string) $slot->end_time);
            });

            $seenSlotKeys = [];
            $updated = 0;
            $created = 0;
            $removed = 0;

            foreach ($latestMatrix->slots as $matrixSlot) {
                $slotKey = $this->buildSlotKey($matrixSlot->slot_date, (string) $matrixSlot->start_time, (string) $matrixSlot->end_time);
                $seenSlotKeys[$slotKey] = true;

                $matrixSubjectId = $matrixSlot->is_fixed
                    ? $this->resolveFixedSubjectId($matrixSlot, $eligibleSubjectIds)
                    : null;

                if ($matrixSlot->is_fixed) {
                    $matrixSubjectId = $this->resolveBatchConstrainedSubject(
                        $matrixSlot,
                        $matrixSubjectId,
                        $slotBatchMap,
                        $sectionBatchMap
                    );
                }

                /** @var SectionExamScheduleSlot|null $existingSlot */
                $existingSlot = $existingSlots->get($slotKey);

                if (! $existingSlot) {
                    $schedule->slots()->create([
                        'exam_matrix_slot_id' => $matrixSlot->id,
                        'slot_date' => $matrixSlot->slot_date,
                        'start_time' => $matrixSlot->start_time,
                        'end_time' => $matrixSlot->end_time,
                        'is_fixed' => $matrixSlot->is_fixed,
                        'subject_id' => $matrixSubjectId,
                        'room_id' => null,
                        'is_manual_assignment' => false,
                    ]);

                    $created++;
                    continue;
                }

                $baseUpdates = [
                    'exam_matrix_slot_id' => $matrixSlot->id,
                    'slot_date' => $matrixSlot->slot_date,
                    'start_time' => $matrixSlot->start_time,
                    'end_time' => $matrixSlot->end_time,
                    'is_fixed' => $matrixSlot->is_fixed,
                ];

                $existingSubjectId = $existingSlot->subject_id === null ? null : (int) $existingSlot->subject_id;
                $shouldOverwriteSubject = (bool) $matrixSlot->is_fixed && $matrixSubjectId !== $existingSubjectId;

                if ($shouldOverwriteSubject) {
                    $existingSlot->update(array_merge($baseUpdates, [
                        'subject_id' => $matrixSubjectId,
                        'room_id' => null,
                        'is_manual_assignment' => false,
                    ]));
                    $existingSlot->proctors()->detach();
                    $updated++;
                    continue;
                }

                if (
                    (int) $existingSlot->exam_matrix_slot_id !== (int) $matrixSlot->id
                    || (bool) $existingSlot->is_fixed !== (bool) $matrixSlot->is_fixed
                ) {
                    $existingSlot->update($baseUpdates);
                    $updated++;
                }
            }

            foreach ($existingSlots as $slotKey => $existingSlot) {
                if (isset($seenSlotKeys[$slotKey])) {
                    continue;
                }

                $existingSlot->delete();
                $removed++;
            }

            $schedule->update([
                'exam_matrix_id' => $latestMatrix->id,
                'status' => 'draft',
                'published_at' => null,
                'published_by' => null,
            ]);

            return [
                'schedule' => $schedule->fresh(['section.program', 'slots.subject', 'slots.room', 'slots.proctors']),
                'updated' => $updated,
                'created' => $created,
                'removed' => $removed,
            ];
        });
    }

    public function saveDraftBatch(SectionExamSchedule $schedule, array $slotsData, bool $mergeConfirmed = false): SectionExamSchedule
    {
        $schedule->loadMissing(['section', 'slots.proctors', 'slots.schedule.section']);

        if ($schedule->isPublished()) {
            throw ValidationException::withMessages([
                'schedule' => 'Published schedules must be reset before editing assignments.',
            ]);
        }

        $slots = $schedule->slots->keyBy('id');
        $updates = [];

        foreach ($slotsData as $slotId => $slotPayload) {
            $slotId = (int) $slotId;
            if (! $slots->has($slotId)) {
                continue;
            }

            $slot = $slots->get($slotId);
            if (! $slot instanceof SectionExamScheduleSlot) {
                continue;
            }

            $subjectId = $this->nullableInt(Arr::get($slotPayload, 'subject_id'));
            $roomId = $this->nullableInt(Arr::get($slotPayload, 'room_id'));
            $proctorId = $this->nullableInt(Arr::get($slotPayload, 'proctor_id'));
            $proctorIds = $proctorId !== null ? collect([$proctorId]) : collect();

            $this->validateSubjectEligibility($slot, $subjectId);
            $this->assertRoomEligibility($slot, $roomId, $subjectId);
            $this->assertProctorEligibility($slot, $proctorIds, $mergeConfirmed);

            $updates[] = [
                'slot'        => $slot,
                'subject_id'  => $subjectId,
                'room_id'     => $roomId,
                'proctor_ids' => $proctorIds,
                'has_room'    => array_key_exists('room_id', $slotPayload),
                'has_proctors' => array_key_exists('proctor_id', $slotPayload),
            ];
        }

        DB::transaction(function () use ($updates): void {
            foreach ($updates as $update) {
                /** @var SectionExamScheduleSlot $slot */
                $slot = $update['slot'];

                $updateData = [
                    'subject_id' => $update['subject_id'],
                    'is_manual_assignment' => true,
                ];
                if ($update['has_room']) {
                    $updateData['room_id'] = $update['room_id'];
                }
                $slot->update($updateData);

                if ($update['has_proctors']) {
                    $slot->proctors()->sync($update['proctor_ids']->all());
                }
            }
        });

        return $schedule->fresh(['section.program', 'slots.subject', 'slots.room', 'slots.proctors']);
    }

    public function publishSchedule(SectionExamSchedule $schedule, int $actorId): SectionExamSchedule
    {
        if ($schedule->isPublished()) {
            return $schedule;
        }

        $this->assertSchedulePublishable($schedule);

        $schedule->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $actorId,
        ]);

        return $schedule->fresh(['section.program', 'slots.subject', 'slots.room', 'slots.proctors']);
    }

    public function getUnavailableRoomIdsForSlot(SectionExamScheduleSlot $slot, int $sectionStudentCount): array
    {
        // Rooms already assigned to a different slot at the same time — shown as merge warnings, not hard blocks.
        $mergeWarningRoomIds = SectionExamScheduleSlot::query()
            ->where('id', '!=', $slot->id)
            ->where('slot_date', $slot->slot_date)
            ->where('start_time', $slot->start_time)
            ->where('end_time', $slot->end_time)
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Rooms that cannot fit this section alone — hard block regardless of merge.
        $capacityRoomIds = Room::query()
            ->where('is_available', true)
            ->where('capacity', '<', $sectionStudentCount)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'merge_warning' => $mergeWarningRoomIds,
            'capacity'      => $capacityRoomIds,
        ];
    }

    public function getUnavailableProctorIdsForSlot(SectionExamScheduleSlot $slot): array
    {
        return DB::table('section_exam_schedule_slot_proctors as pivot')
            ->join('section_exam_schedule_slots as slots', 'pivot.section_exam_schedule_slot_id', '=', 'slots.id')
            ->where('slots.id', '!=', $slot->id)
            ->where('slots.slot_date', $slot->slot_date)
            ->where('slots.start_time', $slot->start_time)
            ->where('slots.end_time', $slot->end_time)
            ->pluck('pivot.proctor_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function publishScope(array $scope, int $actorId): int
    {
        $academicYear = (string) Arr::get($scope, 'academic_year');
        $semester = (int) Arr::get($scope, 'semester');
        $examPeriod = (string) Arr::get($scope, 'exam_period');
        $programId = (int) Arr::get($scope, 'program_id');

        $query = SectionExamSchedule::query()
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->where('exam_period', $examPeriod)
            ->where('program_id', $programId)
            ->where('status', 'draft')
            ->with(['slots.proctors']);

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            throw ValidationException::withMessages([
                'scope' => 'No draft schedules found for the selected publish scope.',
            ]);
        }

        foreach ($schedules as $schedule) {
            if (! $schedule instanceof SectionExamSchedule) {
                continue;
            }

            $this->assertSchedulePublishable($schedule);
        }

        return DB::transaction(function () use ($query, $actorId): int {
            return $query->update([
                'status' => 'published',
                'published_at' => now(),
                'published_by' => $actorId,
            ]);
        });
    }

    private function eligibleSubjectIdsForSection(Section $section, int $semester): Collection
    {
        return DB::table('program_subjects')
            ->where('program_id', $section->program_id)
            ->where('year_level', $section->year_level)
            ->where('semester', $semester)
            ->distinct()
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id);
    }

    private function validateSubjectEligibility(SectionExamScheduleSlot $slot, ?int $subjectId): void
    {
        if ($subjectId === null) {
            return;
        }

        $subjectIds = $this->eligibleSubjectIdsForSection(
            $slot->schedule->section,
            (int) $slot->schedule->semester
        );

        if (! $subjectIds->contains($subjectId)) {
            throw ValidationException::withMessages([
                'subject_id' => 'Selected subject is not part of this section\'s enrolled subjects.',
            ]);
        }
    }

    /**
     * Validates room assignment for a slot, handling both the simple (no conflict) and
     * merge (same room already used by another section at the same time) scenarios.
     *
     * Merge is only permitted when every conflicting slot shares the same subject.
     * Capacity is always enforced — using the aggregated student total when merging.
     */
    private function assertRoomEligibility(SectionExamScheduleSlot $slot, ?int $roomId, ?int $subjectId): void
    {
        if ($roomId === null) {
            return;
        }

        $room = Room::find($roomId);
        if (! $room) {
            return;
        }

        $conflictSlots = SectionExamScheduleSlot::query()
            ->with(['schedule.section'])
            ->where('id', '!=', $slot->id)
            ->where('slot_date', $slot->slot_date)
            ->where('start_time', $slot->start_time)
            ->where('end_time', $slot->end_time)
            ->where('room_id', $roomId)
            ->get();

        $currentStudents = $slot->schedule->section->students()->count();

        if ($conflictSlots->isEmpty()) {
            // No merge — apply simple single-section capacity check.
            if ($room->capacity < $currentStudents) {
                throw ValidationException::withMessages([
                    'room_id' => 'Selected room capacity is lower than the section student count.',
                ]);
            }

            return;
        }

        // Merge scenario — all conflicting slots must share the same subject.
        foreach ($conflictSlots as $conflictSlot) {
            $conflictSubjectId = $conflictSlot->subject_id !== null ? (int) $conflictSlot->subject_id : null;

            if ($subjectId === null || $conflictSubjectId === null || $subjectId !== $conflictSubjectId) {
                $sectionCode = $conflictSlot->schedule?->section?->section_code ?? 'another section';
                throw ValidationException::withMessages([
                    'room_id' => "Cannot merge: {$sectionCode} has a different subject assigned in this room and time slot. Merging is only allowed when all sections share the same subject.",
                ]);
            }
        }

        // Subjects match — enforce aggregated capacity across all merged sections.
        $totalStudents = $currentStudents;
        foreach ($conflictSlots as $conflictSlot) {
            $totalStudents += $conflictSlot->schedule?->section?->students()?->count() ?? 0;
        }

        if ($room->capacity < $totalStudents) {
            throw ValidationException::withMessages([
                'room_id' => "Room capacity ({$room->capacity}) is insufficient for the merged group ({$totalStudents} total students across merged sections).",
            ]);
        }
    }

    /**
     * Validates proctor assignment in merge-aware mode.
     *
     * A proctor conflict (same proctor in the same time slot) is allowed when the
     * academic head has explicitly confirmed the merge. Without confirmation the
     * save is blocked so the UI can present a warning modal first.
     */
    private function assertProctorEligibility(SectionExamScheduleSlot $slot, Collection $proctorIds, bool $mergeConfirmed): void
    {
        if ($proctorIds->isEmpty()) {
            return;
        }

        $conflictExists = DB::table('section_exam_schedule_slot_proctors as pivot')
            ->join('section_exam_schedule_slots as slots', 'pivot.section_exam_schedule_slot_id', '=', 'slots.id')
            ->where('slots.id', '!=', $slot->id)
            ->where('slots.slot_date', $slot->slot_date)
            ->where('slots.start_time', $slot->start_time)
            ->where('slots.end_time', $slot->end_time)
            ->whereIn('pivot.proctor_id', $proctorIds->all())
            ->exists();

        if ($conflictExists && ! $mergeConfirmed) {
            throw ValidationException::withMessages([
                'proctor_id' => 'Selected proctor is already assigned to another schedule at this time. Confirm the merge to proceed.',
            ]);
        }
    }

    private function assertSchedulePublishable(SectionExamSchedule $schedule): void
    {
        $schedule = $this->reconcileDraftMatrixAssignments($schedule);
        $blockers = $this->collectPublishBlockers($schedule);

        if (! empty($blockers['missing_fixed_subjects'])) {
            throw ValidationException::withMessages([
                'publish' => 'Cannot publish while fixed slots are missing subjects. First unresolved slot: '.$blockers['missing_fixed_subjects'][0].'.',
            ]);
        }

        if (! empty($blockers['assigned_without_room'])) {
            throw ValidationException::withMessages([
                'publish' => 'Cannot publish while assigned subjects have no room. First unresolved slot: '.$blockers['assigned_without_room'][0].'.',
            ]);
        }

        if (! empty($blockers['assigned_without_proctor'])) {
            throw ValidationException::withMessages([
                'publish' => 'Cannot publish while assigned subjects have no proctor. First unresolved slot: '.$blockers['assigned_without_proctor'][0].'.',
            ]);
        }
    }

    /**
     * @return array{
     *     missing_fixed_subjects: array<int, string>,
     *     assigned_without_room: array<int, string>,
     *     assigned_without_proctor: array<int, string>
     * }
     */
    private function collectPublishBlockers(SectionExamSchedule $schedule): array
    {
        $schedule->loadMissing(['slots.proctors']);
        $expectedFixedSubjects = $this->expectedFixedSubjectsForSchedule($schedule);

        $blockers = [
            'missing_fixed_subjects' => [],
            'assigned_without_room' => [],
            'assigned_without_proctor' => [],
        ];

        foreach ($schedule->slots as $slot) {
            $slotLabel = $this->slotContextLabel($slot);
            $expectedFixedSubject = $expectedFixedSubjects[(int) $slot->id] ?? [
                'requires_subject' => (bool) $slot->is_fixed,
                'subject_id' => null,
            ];

            if ($expectedFixedSubject['requires_subject'] && ! $slot->subject_id) {
                $blockers['missing_fixed_subjects'][] = $slotLabel;
            }

            if ($slot->subject_id && ! $slot->room_id) {
                $blockers['assigned_without_room'][] = $slotLabel;
            }

            if ($slot->subject_id && $slot->proctors->isEmpty()) {
                $blockers['assigned_without_proctor'][] = $slotLabel;
            }
        }

        return $blockers;
    }

    private function resolveFixedSubjectId($matrixSlot, ?Collection $eligibleSubjectIds = null): ?int
    {
        if (! $matrixSlot) {
            return null;
        }

        $subjectIds = $matrixSlot->slotSubjects
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($eligibleSubjectIds) {
            $subjectIds = $subjectIds
                ->filter(fn (int $id) => $eligibleSubjectIds->contains($id))
                ->values();
        }

        return $subjectIds->first();
    }

    /**
     * @param array<string, int> $slotBatchMap
     * @param array<int, int> $sectionBatchMap
     */
    private function resolveBatchConstrainedSubject($matrixSlot, ?int $subjectId, array $slotBatchMap, array $sectionBatchMap): ?int
    {
        if ($subjectId === null || ! $matrixSlot) {
            return $subjectId;
        }

        if (! $this->subjectHasBatchConstraint($subjectId, $slotBatchMap)) {
            return $subjectId;
        }

        $slotId = (int) $matrixSlot->id;
        $batchKey = $this->batchService->subjectSlotKey($subjectId, $slotId);

        if (! array_key_exists($batchKey, $slotBatchMap)) {
            return null;
        }

        $sectionBatchNo = $sectionBatchMap[$subjectId] ?? null;
        if ($sectionBatchNo === null) {
            return null;
        }

        return (int) $sectionBatchNo === (int) $slotBatchMap[$batchKey]
            ? $subjectId
            : null;
    }

    /**
     * @param array<string, int> $slotBatchMap
     */
    private function subjectHasBatchConstraint(int $subjectId, array $slotBatchMap): bool
    {
        $prefix = $subjectId.':';

        foreach (array_keys($slotBatchMap) as $key) {
            if (str_starts_with((string) $key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function reconcileDraftMatrixAssignments(SectionExamSchedule $schedule): SectionExamSchedule
    {
        if ($schedule->isPublished()) {
            return $schedule;
        }

        $schedule->loadMissing([
            'section.program',
            'matrix.slots.slotSubjects',
            'slots.subject',
            'slots.room',
            'slots.proctors',
            'slots.matrixSlot.slotSubjects',
        ]);

        if (! $schedule->matrix || ! $schedule->section) {
            return $schedule;
        }

        $this->batchService->syncDuplicateSubjectBatches($schedule->matrix);

        $slotBatchMap = $this->batchService->slotBatchMap($schedule->matrix);
        $sectionBatchMap = $this->batchService->sectionBatchMap($schedule->matrix, $schedule->section);
        $eligibleSubjectIds = $this->eligibleSubjectIdsForSection($schedule->section, (int) $schedule->semester);

        $didUpdate = false;

        DB::transaction(function () use ($schedule, $eligibleSubjectIds, $slotBatchMap, $sectionBatchMap, &$didUpdate): void {
            foreach ($schedule->slots as $slot) {
                if (! $slot->is_fixed) {
                    continue;
                }

                $resolvedSubjectId = $this->resolveFixedSubjectId($slot->matrixSlot, $eligibleSubjectIds);
                $resolvedSubjectId = $this->resolveBatchConstrainedSubject(
                    $slot->matrixSlot,
                    $resolvedSubjectId,
                    $slotBatchMap,
                    $sectionBatchMap
                );

                $currentSubjectId = $slot->subject_id === null ? null : (int) $slot->subject_id;

                if ($currentSubjectId === $resolvedSubjectId) {
                    continue;
                }

                $slot->update([
                    'subject_id' => $resolvedSubjectId,
                    'room_id' => null,
                    'is_manual_assignment' => false,
                ]);
                $slot->proctors()->detach();
                $didUpdate = true;
            }
        });

        if (! $didUpdate) {
            return $schedule;
        }

        return $schedule->fresh(['section.program', 'slots.subject', 'slots.room', 'slots.proctors']) ?? $schedule;
    }

    /**
     * @return array<int, array{requires_subject: bool, subject_id: ?int}>
     */
    private function expectedFixedSubjectsForSchedule(SectionExamSchedule $schedule): array
    {
        $schedule->loadMissing([
            'section',
            'matrix.slots.slotSubjects',
            'slots.matrixSlot.slotSubjects',
        ]);

        if (! $schedule->matrix || ! $schedule->section) {
            return [];
        }

        $this->batchService->syncDuplicateSubjectBatches($schedule->matrix);

        $slotBatchMap = $this->batchService->slotBatchMap($schedule->matrix);
        $sectionBatchMap = $this->batchService->sectionBatchMap($schedule->matrix, $schedule->section);
        $eligibleSubjectIds = $this->eligibleSubjectIdsForSection($schedule->section, (int) $schedule->semester);

        $expectedSubjects = [];

        foreach ($schedule->slots as $slot) {
            if (! $slot->is_fixed) {
                continue;
            }

            $resolvedSubjectId = $this->resolveFixedSubjectId($slot->matrixSlot, $eligibleSubjectIds);
            $expectedSubjectId = $this->resolveBatchConstrainedSubject(
                $slot->matrixSlot,
                $resolvedSubjectId,
                $slotBatchMap,
                $sectionBatchMap
            );

            // Matrix slot had subject entries, but none are eligible for this section/program.
            $isProgramIneligibleFiltered = false;
            if ($slot->matrixSlot && $slot->matrixSlot->slotSubjects->isNotEmpty() && $resolvedSubjectId === null) {
                $isProgramIneligibleFiltered = true;
            }

            $isDuplicateFiltered = false;
            if ($resolvedSubjectId !== null && $slot->matrixSlot) {
                $batchKey = $this->batchService->subjectSlotKey($resolvedSubjectId, (int) $slot->matrixSlot->id);
                $isDuplicateFiltered = array_key_exists($batchKey, $slotBatchMap) && $expectedSubjectId === null;
            }

            $isFiltered = $isProgramIneligibleFiltered || $isDuplicateFiltered;

            $expectedSubjects[(int) $slot->id] = [
                'requires_subject' => ! $isFiltered,
                'subject_id' => $expectedSubjectId,
            ];
        }

        return $expectedSubjects;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function buildSlotKey(mixed $slotDate, string $startTime, string $endTime): string
    {
        $dateValue = $slotDate instanceof \DateTimeInterface
            ? $slotDate->format('Y-m-d')
            : (string) $slotDate;

        return $dateValue.'|'.$startTime.'|'.$endTime;
    }

    private function slotContextLabel(SectionExamScheduleSlot $slot): string
    {
        $dateValue = $slot->slot_date instanceof \DateTimeInterface
            ? $slot->slot_date->format('Y-m-d')
            : (string) $slot->slot_date;

        $startTime = $this->formatTimeLabel((string) $slot->start_time);
        $endTime = $this->formatTimeLabel((string) $slot->end_time);

        return $dateValue.' '.$startTime.'-'.$endTime;
    }

    private function formatTimeLabel(string $time): string
    {
        if (preg_match('/^\d{2}:\d{2}/', $time) === 1) {
            return substr($time, 0, 5);
        }

        return $time;
    }
}
