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

    public function test_matrix_upload_is_blocked_when_duplicate_subject_batch_assignment_is_missing(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $matrix = $this->createUploadedMatrix($program, $user, [
            'status' => 'draft',
            'uploaded_at' => null,
            'uploaded_by' => null,
        ]);

        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $subject = Subject::query()->create([
            'code' => 'NSTP101',
            'course_serial_number' => 'CSN-NSTP101',
            'name' => 'NSTP 1',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $slotA = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        $slotB = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '13:00:00',
            'end_time' => '14:30:00',
            'is_fixed' => true,
            'sort_order' => 5,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotA->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotB->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('academic-head.general-exam-matrix.index'))
            ->post(route('academic-head.general-exam-matrix.upload', $matrix));

        $response->assertRedirect(route('academic-head.general-exam-matrix.index'));
        $response->assertSessionHasErrors('upload');

        $matrix->refresh();
        $this->assertSame('draft', $matrix->status);

        // Ensure related scope really existed and therefore required classification.
        $this->assertNotNull($section->id);
    }

    public function test_matrix_upload_succeeds_after_duplicate_subject_batch_assignment_is_saved(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $matrix = $this->createUploadedMatrix($program, $user, [
            'status' => 'draft',
            'uploaded_at' => null,
            'uploaded_by' => null,
        ]);

        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $subject = Subject::query()->create([
            'code' => 'NSTP101',
            'course_serial_number' => 'CSN-NSTP101',
            'name' => 'NSTP 1',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $slotA = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        $slotB = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '13:00:00',
            'end_time' => '14:30:00',
            'is_fixed' => true,
            'sort_order' => 5,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotA->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotB->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('academic-head.general-exam-matrix.save-duplicate-classification', $matrix), [
                'assignments' => [
                    $subject->id => [
                        $section->id => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('academic-head.general-exam-matrix.index'));

        $response = $this->actingAs($user)
            ->post(route('academic-head.general-exam-matrix.upload', $matrix));

        $response->assertRedirect(route('academic-head.general-exam-matrix.index'));

        $matrix->refresh();
        $this->assertSame('uploaded', $matrix->status);
        $this->assertDatabaseHas('exam_matrix_subject_batch_section_assignments', [
            'exam_matrix_id' => $matrix->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'batch_no' => 1,
        ]);
    }

    public function test_schedule_generation_leaves_duplicate_fixed_slots_unassigned_without_batch_mapping(): void
    {
        $user = $this->createAcademicHead();
        $program = $this->createProgram();
        $this->createAcademicSetting('2025-2026', '1st Semester', 'Prelim');

        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $subject = Subject::query()->create([
            'code' => 'NSTP101',
            'course_serial_number' => 'CSN-NSTP101',
            'name' => 'NSTP 1',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $matrix = $this->createUploadedMatrix($program, $user);

        $slotA = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        $slotB = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '13:00:00',
            'end_time' => '14:30:00',
            'is_fixed' => true,
            'sort_order' => 5,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotA->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotB->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->post(route('academic-head.schedules.generate'), [
                'program_id' => $program->id,
                'year_level' => 1,
                'semester' => 1,
                'exam_period' => 'Prelim',
                'section_id' => $section->id,
            ]);

        $response->assertRedirect(route('academic-head.schedules.index', [
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $program->id,
            'year_level' => 1,
            'section_id' => $section->id,
        ]));

        $schedule = SectionExamSchedule::query()
            ->where('section_id', $section->id)
            ->where('exam_matrix_id', $matrix->id)
            ->firstOrFail();

        $this->assertDatabaseHas('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'subject_id' => null,
        ]);
        $this->assertDatabaseHas('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'start_time' => '13:00:00',
            'end_time' => '14:30:00',
            'subject_id' => null,
        ]);
    }

    public function test_schedule_generation_prefers_selected_program_matrix_when_other_program_matrix_is_newer(): void
    {
        $user = $this->createAcademicHead();
        $this->createAcademicSetting('2025-2026', '1st Semester', 'Prelim');

        $targetProgram = Program::query()->create([
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);

        $otherProgram = Program::query()->create([
            'name' => 'Bachelor of Science in Accountancy',
            'code' => 'BSA',
        ]);

        $section = Section::query()->create([
            'program_id' => $targetProgram->id,
            'year_level' => 1,
            'section_code' => 'BSIT-1A',
        ]);

        $targetSubject = Subject::query()->create([
            'code' => 'IT101',
            'course_serial_number' => 'CSN-IT101',
            'name' => 'Intro to IT',
            'units' => 3,
        ]);

        $otherSubject = Subject::query()->create([
            'code' => 'ACC101',
            'course_serial_number' => 'CSN-ACC101',
            'name' => 'Fundamentals of Accounting',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            ['program_id' => $targetProgram->id, 'subject_id' => $targetSubject->id, 'year_level' => 1, 'semester' => 1],
            ['program_id' => $otherProgram->id, 'subject_id' => $otherSubject->id, 'year_level' => 1, 'semester' => 1],
        ]);

        $targetMatrix = $this->createUploadedMatrix($targetProgram, $user, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'uploaded_at' => now()->subDay(),
        ]);

        $otherMatrix = $this->createUploadedMatrix($otherProgram, $user, [
            'created_at' => now(),
            'updated_at' => now(),
            'uploaded_at' => now(),
        ]);

        $targetSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $targetMatrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $targetSlot->id,
            'subject_id' => $targetSubject->id,
            'sort_order' => 1,
        ]);

        $otherSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $otherMatrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $otherSlot->id,
            'subject_id' => $otherSubject->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->post(route('academic-head.schedules.generate'), [
                'program_id' => $targetProgram->id,
                'year_level' => 1,
                'semester' => 1,
                'exam_period' => 'Prelim',
                'section_id' => $section->id,
            ]);

        $response->assertRedirect(route('academic-head.schedules.index', [
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $targetProgram->id,
            'year_level' => 1,
            'section_id' => $section->id,
        ]));

        $schedule = SectionExamSchedule::query()
            ->where('section_id', $section->id)
            ->where('academic_year', '2025-2026')
            ->where('semester', 1)
            ->where('exam_period', 'Prelim')
            ->firstOrFail();

        $this->assertSame($targetMatrix->id, $schedule->exam_matrix_id);

        $this->assertDatabaseHas('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'subject_id' => $targetSubject->id,
        ]);

        $this->assertDatabaseMissing('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'subject_id' => $otherSubject->id,
        ]);
    }

    public function test_fetch_matrix_keeps_program_specific_matrix_even_if_other_program_matrix_is_newer(): void
    {
        $user = $this->createAcademicHead();

        $targetProgram = Program::query()->create([
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);

        $otherProgram = Program::query()->create([
            'name' => 'Bachelor of Science in Accountancy',
            'code' => 'BSA',
        ]);

        $section = Section::query()->create([
            'program_id' => $targetProgram->id,
            'year_level' => 1,
            'section_code' => 'BSIT-1A',
        ]);

        $targetSubject = Subject::query()->create([
            'code' => 'IT102',
            'course_serial_number' => 'CSN-IT102',
            'name' => 'Programming Fundamentals',
            'units' => 3,
        ]);

        $otherSubject = Subject::query()->create([
            'code' => 'ACC102',
            'course_serial_number' => 'CSN-ACC102',
            'name' => 'Managerial Accounting',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            ['program_id' => $targetProgram->id, 'subject_id' => $targetSubject->id, 'year_level' => 1, 'semester' => 1],
            ['program_id' => $otherProgram->id, 'subject_id' => $otherSubject->id, 'year_level' => 1, 'semester' => 1],
        ]);

        $targetMatrix = $this->createUploadedMatrix($targetProgram, $user, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'uploaded_at' => now()->subDay(),
        ]);

        $targetMatrixSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $targetMatrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $targetMatrixSlot->id,
            'subject_id' => $targetSubject->id,
            'sort_order' => 1,
        ]);

        $schedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $targetMatrix->id,
            'section_id' => $section->id,
            'academic_year' => $targetMatrix->academic_year,
            'semester' => $targetMatrix->semester,
            'exam_period' => $targetMatrix->exam_period,
            'program_id' => $targetProgram->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $targetMatrixSlot->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => $targetSubject->id,
            'room_id' => null,
            'is_manual_assignment' => false,
        ]);

        $otherMatrix = $this->createUploadedMatrix($otherProgram, $user, [
            'created_at' => now(),
            'updated_at' => now(),
            'uploaded_at' => now(),
        ]);

        $otherMatrixSlot = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $otherMatrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $otherMatrixSlot->id,
            'subject_id' => $otherSubject->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->post(route('academic-head.schedules.fetch-matrix', $schedule));

        $response->assertRedirect();

        $schedule->refresh();

        $this->assertSame($targetMatrix->id, $schedule->exam_matrix_id);

        $this->assertDatabaseHas('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'subject_id' => $targetSubject->id,
        ]);

        $this->assertDatabaseMissing('section_exam_schedule_slots', [
            'section_exam_schedule_id' => $schedule->id,
            'subject_id' => $otherSubject->id,
        ]);
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

    public function test_upload_clears_stale_duplicate_batch_assignment_before_publish(): void
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
            'code' => 'NSTP101',
            'course_serial_number' => 'CSN-NSTP101',
            'name' => 'NSTP 1',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $matrix = $this->createUploadedMatrix($program, $user);

        $slotA = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'sort_order' => 1,
        ]);

        $slotB = ExamMatrixSlot::query()->create([
            'exam_matrix_id' => $matrix->id,
            'slot_date' => '2026-04-01',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'is_fixed' => true,
            'sort_order' => 2,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotA->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        ExamMatrixSlotSubject::query()->create([
            'exam_matrix_slot_id' => $slotB->id,
            'subject_id' => $subject->id,
            'sort_order' => 1,
        ]);

        app(\App\Services\AcademicHead\ExamMatrixBatchService::class)->syncDuplicateSubjectBatches($matrix);

        DB::table('exam_matrix_subject_batch_section_assignments')->insert([
            'exam_matrix_id' => $matrix->id,
            'subject_id' => $subject->id,
            'program_id' => $program->id,
            'year_level' => 1,
            'section_id' => $section->id,
            'batch_no' => 1,
            'created_at' => now(),
            'updated_at' => now(),
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

        $keptSlot = SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $slotA->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'is_manual_assignment' => false,
        ]);
        $keptSlot->proctors()->attach($proctor->id);

        $staleSlot = SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'exam_matrix_slot_id' => $slotB->id,
            'slot_date' => '2026-04-01',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'is_fixed' => true,
            'subject_id' => $subject->id,
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
        $staleSlot->refresh();

        $this->assertSame('published', $schedule->status);
        $this->assertNull($staleSlot->subject_id);
        $this->assertNull($staleSlot->room_id);
        $this->assertFalse((bool) $staleSlot->is_manual_assignment);
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

    public function test_schedules_index_ignores_fixed_subjects_ineligible_for_selected_program(): void
    {
        $user = $this->createAcademicHead();
        $this->createAcademicSetting('2025-2026', '1st Semester', 'Prelim');

        $sourceProgram = Program::query()->create([
            'name' => 'Bachelor of Science in Computer Science',
            'code' => 'BSCS',
        ]);

        $targetProgram = Program::query()->create([
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);

        $sourceSection = Section::query()->create([
            'program_id' => $sourceProgram->id,
            'year_level' => 1,
            'section_code' => 'BSCS-1A',
        ]);

        $targetSection = Section::query()->create([
            'program_id' => $targetProgram->id,
            'year_level' => 1,
            'section_code' => 'BSIT-1A',
        ]);

        $ineligibleSubject = Subject::query()->create([
            'code' => 'CS101',
            'course_serial_number' => 'CSN-CS101',
            'name' => 'Introduction to Computing',
            'units' => 3,
        ]);

        $nstpSubject = Subject::query()->create([
            'code' => 'NSTP101',
            'course_serial_number' => 'CSN-NSTP101',
            'name' => 'National Service Training Program 1',
            'units' => 3,
        ]);

        DB::table('program_subjects')->insert([
            [
                'program_id' => $sourceProgram->id,
                'subject_id' => $ineligibleSubject->id,
                'year_level' => 1,
                'semester' => 1,
            ],
            [
                'program_id' => $targetProgram->id,
                'subject_id' => $nstpSubject->id,
                'year_level' => 1,
                'semester' => 1,
            ],
        ]);

        $matrix = $this->createUploadedMatrix($sourceProgram, $user);
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
            'subject_id' => $ineligibleSubject->id,
            'sort_order' => 1,
        ]);

        SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $sourceSection->id,
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $sourceProgram->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $targetSchedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $targetSection->id,
            'academic_year' => '2025-2026',
            'semester' => 1,
            'exam_period' => 'Prelim',
            'program_id' => $targetProgram->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $targetSchedule->id,
            'exam_matrix_slot_id' => $fixedMatrixSlot->id,
            'slot_date' => '2026-04-01',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
            'subject_id' => $ineligibleSubject->id,
            'room_id' => null,
            'is_manual_assignment' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('academic-head.schedules.index', [
                'semester' => 1,
                'exam_period' => 'Prelim',
                'program_id' => $targetProgram->id,
                'year_level' => 1,
                'section_id' => $targetSection->id,
            ]));

        $response->assertOk();
        $response->assertDontSee('Fixed slots missing subjects');
        $response->assertDontSee('Upload blocked:');

        $targetSchedule->refresh();
        $this->assertDatabaseHas('section_exam_schedule_slots', [
            'id' => $targetSchedule->slots()->firstOrFail()->id,
            'subject_id' => null,
        ]);
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
