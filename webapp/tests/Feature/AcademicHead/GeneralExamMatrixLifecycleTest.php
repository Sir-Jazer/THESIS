<?php

namespace Tests\Feature\AcademicHead;

use App\Models\ExamMatrix;
use App\Models\ExamMatrixSlot;
use App\Models\ExamMatrixSlotSubject;
use App\Models\AcademicSetting;
use App\Models\Program;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionExamSchedule;
use App\Models\SectionExamScheduleSlot;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GeneralExamMatrixLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_matrix_edit_page_is_accessible(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $matrix = $this->createUploadedMatrix($program, $user);

        $response = $this->actingAs($user)
            ->get(route('academic-head.general-exam-matrix.edit', $matrix));

        $response->assertOk();
        $response->assertSee('Edit General Exam Matrix');
    }

    public function test_uploaded_matrix_can_be_updated(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $matrix = $this->createUploadedMatrix($program, $user, [
            'name' => 'Original Name',
        ]);

        $payload = $this->validUpdatePayload([
            'name' => 'Corrected Matrix Name',
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
        ]);

        $response = $this->actingAs($user)
            ->put(route('academic-head.general-exam-matrix.update', $matrix), $payload);

        $response->assertRedirect(route('academic-head.general-exam-matrix.index'));

        $this->assertDatabaseHas('exam_matrices', [
            'id' => $matrix->id,
            'name' => 'Corrected Matrix Name',
            'status' => 'uploaded',
        ]);

        $this->assertDatabaseCount('exam_matrix_slots', 28);
    }

    public function test_uploaded_matrix_can_be_reuploaded_and_refresh_upload_metadata(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();

        $matrix = $this->createUploadedMatrix($program, $user, [
            'uploaded_at' => now()->subDay(),
        ]);

        $schedule = $this->createScheduleForMatrix($matrix, $program, $user);

        $response = $this->actingAs($user)
            ->post(route('academic-head.general-exam-matrix.upload', $matrix));

        $response->assertRedirect(route('academic-head.general-exam-matrix.index'));

        $matrix->refresh();
        $schedule->refresh();

        $this->assertSame('uploaded', $matrix->status);
        $this->assertSame($user->id, $matrix->uploaded_by);
        $this->assertTrue($matrix->uploaded_at !== null && $matrix->uploaded_at->isAfter(now()->subMinute()));

        // Re-upload should not auto-regenerate or re-link existing schedules.
        $this->assertSame($matrix->id, $schedule->exam_matrix_id);
        $this->assertSame('draft', $schedule->status);
    }

    public function test_uploaded_matrix_can_be_deleted_and_cascades_schedules(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $matrix = $this->createUploadedMatrix($program, $user);
        $schedule = $this->createScheduleForMatrix($matrix, $program, $user);

        $response = $this->actingAs($user)
            ->delete(route('academic-head.general-exam-matrix.destroy', $matrix));

        $response->assertRedirect(route('academic-head.general-exam-matrix.index'));

        $this->assertDatabaseMissing('exam_matrices', ['id' => $matrix->id]);
        $this->assertDatabaseMissing('section_exam_schedules', ['id' => $schedule->id]);
    }

    public function test_draft_schedule_can_fetch_latest_matrix_updates_without_recreating_schedule(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $subjectA = Subject::query()->create([
            'code' => 'MATH101',
            'course_serial_number' => 'CSN-MATH101',
            'name' => 'College Algebra',
            'units' => 3,
        ]);
        $subjectB = Subject::query()->create([
            'code' => 'ENG101',
            'course_serial_number' => 'CSN-ENG101',
            'name' => 'Communication Skills',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            ['program_id' => $program->id, 'subject_id' => $subjectA->id, 'year_level' => 1, 'semester' => 1],
            ['program_id' => $program->id, 'subject_id' => $subjectB->id, 'year_level' => 1, 'semester' => 1],
        ]);

        $matrixV1 = $this->createUploadedMatrix($program, $user, [
            'uploaded_at' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $matrixV1Slot1 = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrixV1->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);
        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $matrixV1Slot1->id,
            'subject_id' => $subjectA->id,
            'sort_order' => 1,
        ]);

        $matrixV1Slot2 = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrixV1->id,
            'slot_date' => '2026-04-01',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'is_fixed' => false,
            'sort_order' => 2,
        ]);

        $schedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrixV1->id,
            'section_id' => $section->id,
            'academic_year' => $matrixV1->academic_year,
            'semester' => $matrixV1->semester,
            'exam_period' => $matrixV1->exam_period,
            'program_id' => $program->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $room = Room::query()->create([
            'name' => 'Room 101',
            'capacity' => 40,
            'is_available' => true,
        ]);

        $scheduleSlot1 = SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $matrixV1Slot1->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => $subjectA->id,
            'room_id' => $room->id,
            'is_manual_assignment' => true,
        ]);

        SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $matrixV1Slot2->id,
            'slot_date' => '2026-04-01',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'is_fixed' => false,
            'subject_id' => null,
            'room_id' => null,
            'is_manual_assignment' => false,
        ]);

        // Simulate matrix reupload by replacing slots under the same matrix record.
        $matrixV1Slot1->delete();
        $matrixV1Slot2->delete();

        $matrixV1->update([
            'uploaded_at' => now(),
            'uploaded_by' => $user->id,
        ]);

        $matrixV2Slot1 = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrixV1->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);
        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $matrixV2Slot1->id,
            'subject_id' => $subjectB->id,
            'sort_order' => 1,
        ]);

        $matrixV2Slot3 = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrixV1->id,
            'slot_date' => '2026-04-01',
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'is_fixed' => true,
            'sort_order' => 3,
        ]);
        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $matrixV2Slot3->id,
            'subject_id' => $subjectA->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->post(route('academic-head.schedules.fetch-matrix', $schedule));

        $response->assertRedirect();

        $schedule->refresh();
        $scheduleSlot1->refresh();

        $this->assertSame($matrixV1->id, $schedule->exam_matrix_id);
        $this->assertSame('draft', $schedule->status);

        // Existing matching slot should be overwritten because new matrix fixed assignment changed.
        $this->assertSame($subjectB->id, $scheduleSlot1->subject_id);
        $this->assertNull($scheduleSlot1->room_id);
        $this->assertFalse((bool) $scheduleSlot1->is_manual_assignment);

        // Old matrix slot that no longer exists should be removed.
        $this->assertDatabaseMissing('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
        ]);

        // New slot from latest matrix should be added.
        $this->assertDatabaseHas('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'subject_id' => $subjectA->id,
        ]);
    }

    public function test_edit_schedule_page_shows_general_matrix_yellow_indicator_on_subject_dropdown(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $subject = Subject::query()->create([
            'code' => 'CS101',
            'course_serial_number' => 'CSN-CS101',
            'name' => 'Introduction to Computing',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $matrix = $this->createUploadedMatrix($program, $user);

        $matrixSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $matrixSlot->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        $schedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $section->id,
            'academic_year' => $matrix->academic_year,
            'semester' => $matrix->semester,
            'exam_period' => $matrix->exam_period,
            'program_id' => $program->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $matrixSlot->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => $subject->id,
            'room_id' => null,
            'is_manual_assignment' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('academic-head.schedules.edit', $schedule));

        $response->assertOk();
        $response->assertSee('Yellow subject dropdown means the selected subject is strictly from the General Exam Matrix.');
        $response->assertSee('data-slot-subject-select="true"', false);
        $response->assertSee('data-matrix-subject-ids=', false);
        $response->assertSee((string) $subject->id, false);
        $response->assertSee('(General Matrix Assigned)');
    }

    public function test_upload_succeeds_when_only_non_fixed_slots_are_unassigned(): void
    {
        $user = $this->createAcademicHead();
        $proctor = $this->createProctor();
        $program = $this->createProgram();

        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $subject = Subject::query()->create([
            'code' => 'CS101',
            'course_serial_number' => 'CSN-CS101',
            'name' => 'Introduction to Computing',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $matrix = $this->createUploadedMatrix($program, $user);

        $fixedMatrixSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $fixedMatrixSlot->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        $nonFixedMatrixSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'is_fixed' => false,
            'sort_order' => 2,
        ]);

        $schedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $section->id,
            'academic_year' => $matrix->academic_year,
            'semester' => $matrix->semester,
            'exam_period' => $matrix->exam_period,
            'program_id' => $program->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $room = Room::query()->create([
            'name' => 'Room 101',
            'capacity' => 40,
            'is_available' => true,
        ]);

        $fixedScheduleSlot = SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $fixedMatrixSlot->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'is_manual_assignment' => false,
        ]);
        $fixedScheduleSlot->proctors()->attach($proctor->id);

        SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $nonFixedMatrixSlot->id,
            'slot_date' => '2026-04-01',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'is_fixed' => false,
            'subject_id' => null,
            'room_id' => null,
            'is_manual_assignment' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('academic-head.schedules.upload', $schedule));

        $response->assertRedirect(route('academic-head.schedules.index', [
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $program->id,
            'year_level' => 1,
            'section_id' => $section->id,
        ]));

        $schedule->refresh();
        $this->assertSame('published', $schedule->status);
    }

    public function test_upload_fails_with_slot_context_when_fixed_slot_subject_is_missing(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $matrix = $this->createUploadedMatrix($program, $user);
        $fixedMatrixSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        $schedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $section->id,
            'academic_year' => $matrix->academic_year,
            'semester' => $matrix->semester,
            'exam_period' => $matrix->exam_period,
            'program_id' => $program->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $fixedMatrixSlot->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => null,
            'room_id' => null,
            'is_manual_assignment' => false,
        ]);

        $indexUrl = route('academic-head.schedules.index', [
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $program->id,
            'year_level' => 1,
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($user)
            ->from($indexUrl)
            ->post(route('academic-head.schedules.upload', $schedule));

        $response->assertRedirect($indexUrl);
        $response->assertSessionHasErrors('publish');
        $this->assertStringContainsString(
            'First unresolved slot: 2026-04-01 07:00-08:30.',
            session('errors')->first('publish')
        );

        $schedule->refresh();
        $this->assertSame('draft', $schedule->status);
    }

    public function test_schedules_index_shows_upload_blockers_and_slot_context(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $this->createAcademicSetting('2025-2026', '1st Semester', 'Prelim');

        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $matrix = $this->createUploadedMatrix($program, $user);
        $fixedMatrixSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        $schedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $section->id,
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $program->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $fixedMatrixSlot->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => null,
            'room_id' => null,
            'is_manual_assignment' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('academic-head.schedules.index', [
                'semester' => 1,
                'exam_period' => 'Prelim',
                'program_id' => $program->id,
                'year_level' => 1,
                'section_id' => $section->id,
            ]));

        $response->assertOk();
        $response->assertSee('Upload blocked: 1 issue(s) found.');
        $response->assertSee('Fixed slots missing subjects (1):');
        $response->assertSee('2026-04-01 07:00-08:30');
        $response->assertSee('Resolve upload blockers listed below before publishing.', false);
    }

    private function createAcademicHead(): User
    {
        return User::factory()->create([
            'role' => 'academic_head',
            'status' => 'active',
        ]);
    }

    private function createProctor(): User
    {
        return User::factory()->create([
            'role' => 'proctor',
            'status' => 'active',
        ]);
    }

    private function createAcademicSetting(string $academicYear, string $semester, string $examPeriod): AcademicSetting
    {
        return AcademicSetting::query()->create([
            'academic_year' => $academicYear,
            'semester' => $semester,
            'exam_period' => $examPeriod,
        ]);
    }

    private function createProgram(): Program
    {
        return Program::query()->create([
            'name' => 'Bachelor of Science in Computer Science',
            'code' => 'BSCS',
        ]);
    }

    private function createUploadedMatrix(Program $program, User $user, array $overrides = []): ExamMatrix
    {
        return ExamMatrix::query()->create(array_merge([
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $program->id,
            'name' => 'General Exam Matrix',
            'status' => 'uploaded',
            'uploaded_at' => now()->subHour(),
            'uploaded_by' => $user->id,
            'created_by' => $user->id,
        ], $overrides));
    }

    private function createScheduleForMatrix(ExamMatrix $matrix, Program $program, User $user): SectionExamSchedule
    {
        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        return SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $section->id,
            'academic_year' => $matrix->academic_year,
            'semester' => $matrix->semester,
            'exam_period' => $matrix->exam_period,
            'program_id' => $program->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
    }

    private function validUpdatePayload(array $overrides = []): array
    {
        $examDays = [];

        for ($dayIndex = 0; $dayIndex < 4; $dayIndex++) {
            $periods = [];
            for ($periodIndex = 0; $periodIndex < 7; $periodIndex++) {
                $periods[] = [
                    'subject_ids' => [],
                ];
            }

            $examDays[] = [
                'date' => Carbon::create(2026, 4, 1)->addDays($dayIndex)->toDateString(),
                'periods' => $periods,
            ];
        }

        return array_merge([
            'name' => 'Updated General Matrix',
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
            'exam_days' => $examDays,
        ], $overrides);
    }
}
