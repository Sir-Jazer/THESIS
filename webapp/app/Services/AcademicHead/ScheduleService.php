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

        return DB::transaction(function () use ($matrix, $section, $actorId): SectionExamSchedule {
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
        $schedule->loadMissing(['section', 'slots.matrixSlot.slotSubjects']);
        $eligibleSubjectIds = $this->eligibleSubjectIdsForSection($schedule->section, (int) $schedule->semester);

        DB::transaction(function () use ($schedule, $eligibleSubjectIds): void {
            foreach ($schedule->slots as $slot) {
                $slot->proctors()->detach();

                if ($slot->is_fixed) {
                    $slot->update([
                        'subject_id' => $this->resolveFixedSubjectId($slot->matrixSlot, $eligibleSubjectIds),
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
            ->where('academic_year', $schedule->academic_year)
            ->where('semester', (int) $schedule->semester)
            ->where('exam_period', $schedule->exam_period)
            ->where('status', 'uploaded')
            ->latest()
            ->first();

        if (! $latestMatrix) {
            throw ValidationException::withMessages([
                'matrix' => 'No uploaded General Exam Matrix found for this schedule context.',
            ]);
        }

        $latestMatrix->loadMissing(['slots.slotSubjects']);
        $eligibleSubjectIds = $this->eligibleSubjectIdsForSection($schedule->section, (int) $schedule->semester);

        return DB::transaction(function () use ($schedule, $latestMatrix, $eligibleSubjectIds): array {
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
                $shouldOverwriteSubject = $matrixSubjectId !== null && $matrixSubjectId !== $existingSubjectId;

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

    public function assignSlot(SectionExamScheduleSlot $slot, array $payload): SectionExamScheduleSlot
    {
        $slot->loadMissing(['schedule.section.students.user.subjects', 'proctors']);

        if ($slot->schedule->isPublished()) {
            throw ValidationException::withMessages([
                'slot' => 'Published schedules must be reset before editing assignments.',
            ]);
        }

        $subjectId = Arr::get($payload, 'subject_id');
        $roomId = Arr::get($payload, 'room_id');
        $proctorIds = collect(Arr::get($payload, 'proctor_ids', []))->map(fn ($id) => (int) $id)->unique()->values();

        $this->validateSubjectEligibility($slot, $subjectId);
        $this->assertRoomAvailability($slot, $roomId);
        $this->assertProctorAvailability($slot, $proctorIds);
        $this->assertRoomCapacity($slot, $roomId);

        DB::transaction(function () use ($slot, $subjectId, $roomId, $proctorIds): void {
            $slot->update([
                'subject_id' => $subjectId,
                'room_id' => $roomId,
                'is_manual_assignment' => true,
            ]);

            $slot->proctors()->sync($proctorIds->all());
        });

        return $slot->fresh(['subject', 'room', 'proctors', 'schedule']);
    }

    public function saveDraftBatch(SectionExamSchedule $schedule, array $slotsData): SectionExamSchedule
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
            $proctorIds = collect(Arr::get($slotPayload, 'proctor_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values();

            $this->validateSubjectEligibility($slot, $subjectId);
            $this->assertRoomAvailability($slot, $roomId);
            $this->assertProctorAvailability($slot, $proctorIds);
            $this->assertRoomCapacity($slot, $roomId);

            $updates[] = [
                'slot' => $slot,
                'subject_id' => $subjectId,
                'room_id' => $roomId,
                'proctor_ids' => $proctorIds,
                'has_room' => array_key_exists('room_id', $slotPayload),
                'has_proctors' => array_key_exists('proctor_ids', $slotPayload),
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
        $conflictRoomIds = SectionExamScheduleSlot::query()
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

        $capacityRoomIds = Room::query()
            ->where('is_available', true)
            ->where('capacity', '<', $sectionStudentCount)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'conflict' => $conflictRoomIds,
            'capacity' => $capacityRoomIds,
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

    private function assertRoomAvailability(SectionExamScheduleSlot $slot, ?int $roomId): void
    {
        if ($roomId === null) {
            return;
        }

        $conflictExists = SectionExamScheduleSlot::query()
            ->where('id', '!=', $slot->id)
            ->where('slot_date', $slot->slot_date)
            ->where('start_time', $slot->start_time)
            ->where('end_time', $slot->end_time)
            ->where('room_id', $roomId)
            ->exists();

        if ($conflictExists) {
            throw ValidationException::withMessages([
                'room_id' => 'Selected room is already assigned to another schedule at this time.',
            ]);
        }
    }

    private function assertProctorAvailability(SectionExamScheduleSlot $slot, Collection $proctorIds): void
    {
        if ($proctorIds->isEmpty()) {
            return;
        }

        $conflict = DB::table('section_exam_schedule_slot_proctors as pivot')
            ->join('section_exam_schedule_slots as slots', 'pivot.section_exam_schedule_slot_id', '=', 'slots.id')
            ->where('slots.id', '!=', $slot->id)
            ->where('slots.slot_date', $slot->slot_date)
            ->where('slots.start_time', $slot->start_time)
            ->where('slots.end_time', $slot->end_time)
            ->whereIn('pivot.proctor_id', $proctorIds->all())
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'proctor_ids' => 'One or more proctors are already assigned to another schedule at this time.',
            ]);
        }
    }

    private function assertRoomCapacity(SectionExamScheduleSlot $slot, ?int $roomId): void
    {
        if ($roomId === null) {
            return;
        }

        $sectionStudentCount = $slot->schedule->section->students()->count();

        $room = $slot->room()->getRelated()->newQuery()->find($roomId);
        if (! $room) {
            return;
        }

        if ($room->capacity < $sectionStudentCount) {
            throw ValidationException::withMessages([
                'room_id' => 'Selected room capacity is lower than the section student count.',
            ]);
        }
    }

    private function assertSchedulePublishable(SectionExamSchedule $schedule): void
    {
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

        $blockers = [
            'missing_fixed_subjects' => [],
            'assigned_without_room' => [],
            'assigned_without_proctor' => [],
        ];

        foreach ($schedule->slots as $slot) {
            $slotLabel = $this->slotContextLabel($slot);

            if ($slot->is_fixed && ! $slot->subject_id) {
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
