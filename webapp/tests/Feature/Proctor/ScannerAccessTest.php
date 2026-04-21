<?php

namespace Tests\Feature\Proctor;

use App\Models\Program;
use App\Models\ExamMatrix;
use App\Models\Section;
use App\Models\SectionExamSchedule;
use App\Models\SectionExamScheduleSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScannerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_shows_slots_for_explicitly_assigned_proctor(): void
    {
        [$assignedProctor, , $slot] = $this->seedPublishedSlotWithAdvisoryOnlyProctor();

        $response = $this
            ->actingAs($assignedProctor)
            ->get(route('proctor.scanner.show'));

        $response->assertOk();
        $response->assertSee($slot->schedule->section->section_code);
    }

    public function test_scanner_hides_slots_for_advisory_only_proctor(): void
    {
        [, $advisoryOnlyProctor, $slot] = $this->seedPublishedSlotWithAdvisoryOnlyProctor();

        $response = $this
            ->actingAs($advisoryOnlyProctor)
            ->get(route('proctor.scanner.show'));

        $response->assertOk();
        $response->assertDontSee($slot->schedule->section->section_code);
    }

    public function test_scan_endpoint_returns_403_for_advisory_only_proctor(): void
    {
        [, $advisoryOnlyProctor, $slot] = $this->seedPublishedSlotWithAdvisoryOnlyProctor();

        $response = $this
            ->actingAs($advisoryOnlyProctor)
            ->postJson(route('proctor.scanner.scan'), [
                'slot_id' => $slot->id,
                'qr_token' => 'SAMPLE_TOKEN',
            ]);

        $response->assertForbidden();
        $response->assertJson([
            'ok' => false,
            'message' => 'You are not assigned to this slot.',
        ]);
    }

    public function test_preview_endpoint_returns_403_for_advisory_only_proctor(): void
    {
        [, $advisoryOnlyProctor, $slot] = $this->seedPublishedSlotWithAdvisoryOnlyProctor();

        $response = $this
            ->actingAs($advisoryOnlyProctor)
            ->postJson(route('proctor.scanner.preview'), [
                'slot_id' => $slot->id,
                'qr_token' => 'SAMPLE_TOKEN',
            ]);

        $response->assertForbidden();
        $response->assertJson([
            'ok' => false,
            'message' => 'You are not assigned to this slot.',
        ]);
    }

    private function seedPublishedSlotWithAdvisoryOnlyProctor(): array
    {
        $assignedProctor = User::factory()->create([
            'first_name' => 'Joel',
            'last_name' => 'Pascua',
            'role' => 'proctor',
            'status' => 'active',
        ]);

        $advisoryOnlyProctor = User::factory()->create([
            'first_name' => 'Steven',
            'last_name' => 'Cristobal',
            'role' => 'proctor',
            'status' => 'active',
        ]);

        $program = Program::query()->create([
            'name' => 'Bachelor of Science in Computer Science',
            'code' => 'BSCS',
        ]);

        $section = Section::query()->create([
            'program_id' => $program->id,
            'year_level' => 1,
            'section_code' => 'BSCS101A',
            // Advisory proctor is Steven, but he is NOT explicitly assigned in slot pivot.
            'proctor_id' => $advisoryOnlyProctor->id,
        ]);

        $matrix = ExamMatrix::query()->create([
            'academic_year' => '2025-2026',
            'semester' => 2,
            'exam_period' => 'Prelim',
            'program_id' => $program->id,
            'name' => 'Test Matrix',
            'created_by' => $assignedProctor->id,
        ]);

        $schedule = SectionExamSchedule::query()->create([
            'exam_matrix_id' => $matrix->id,
            'section_id' => $section->id,
            'academic_year' => '2025-2026',
            'semester' => 2,
            'exam_period' => 'Prelim',
            'program_id' => $program->id,
            'status' => 'published',
        ]);

        $slot = SectionExamScheduleSlot::query()->create([
            'section_exam_schedule_id' => $schedule->id,
            'slot_date' => '2026-04-27',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'is_fixed' => true,
        ]);

        $slot->proctors()->sync([$assignedProctor->id]);

        $slot->setRelation('schedule', $schedule->setRelation('section', $section));

        return [$assignedProctor, $advisoryOnlyProctor, $slot];
    }
}
