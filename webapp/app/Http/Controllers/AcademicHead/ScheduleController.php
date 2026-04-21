<?php

namespace App\Http\Controllers\AcademicHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicSetting;
use App\Models\ExamMatrix;
use App\Models\Program;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionExamSchedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\AcademicHead\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request, ScheduleService $service): View
    {
        $setting = AcademicSetting::current();
        $settingSemester = $this->normalizeSemester($setting?->semester) ?? 1;

        $filters = [
            'academic_year' => $setting?->academic_year ?? now()->year . '-' . (now()->year + 1),
            'semester' => (int) ($request->integer('semester') ?: $settingSemester),
            'exam_period' => $request->string('exam_period')->toString(),
            'program_id' => $request->integer('program_id') ?: null,
            'year_level' => $request->integer('year_level') ?: null,
            'section_id' => $request->integer('section_id') ?: null,
        ];

        if ($settingSemester === 1 && $filters['semester'] === 2) {
            $filters['semester'] = 1;
        }

        $sections = Section::with('program:id,code')->orderBy('section_code')->get();
        $selectedSection = $filters['section_id']
            ? $sections->firstWhere('id', $filters['section_id'])
            : null;

        if ($selectedSection && $filters['program_id'] && (int) $selectedSection->program_id !== (int) $filters['program_id']) {
            $selectedSection = null;
            $filters['section_id'] = null;
        }

        if ($selectedSection && $filters['year_level'] && (int) $selectedSection->year_level !== (int) $filters['year_level']) {
            $selectedSection = null;
            $filters['section_id'] = null;
        }

        $selectedSchedule = null;
        $publishReadiness = null;
        $matchingMatrix = null;

        if ($filters['program_id'] && $filters['section_id'] && $filters['exam_period'] !== '') {
            $selectedSchedule = SectionExamSchedule::query()
                ->with(['section.program', 'slots.subject', 'slots.room', 'slots.proctors'])
                ->where('academic_year', $filters['academic_year'])
                ->where('semester', $filters['semester'])
                ->where('exam_period', $filters['exam_period'])
                ->where('program_id', $filters['program_id'])
                ->where('section_id', $filters['section_id'])
                ->first();

            $matchingMatrix = ExamMatrix::query()
                ->where('academic_year', $filters['academic_year'])
                ->where('semester', $filters['semester'])
                ->where('exam_period', $filters['exam_period'])
                ->where('status', 'uploaded')
                ->latest()
                ->first();

            if ($selectedSchedule && $selectedSchedule->status === 'draft') {
                $publishReadiness = $service->getPublishReadiness($selectedSchedule);
            }
        }

        $sectionsJson = $sections->map(fn (Section $section): array => [
            'id' => (int) $section->id,
            'program_id' => (int) $section->program_id,
            'year_level' => (int) $section->year_level,
            'section_code' => $section->section_code,
        ])->values();

        return view('academic-head.schedules.index', [
            'setting' => $setting,
            'filters' => $filters,
            'programs' => Program::orderBy('code')->get(),
            'sections' => $sections,
            'sectionsJson' => $sectionsJson,
            'selectedSection' => $selectedSection,
            'selectedSchedule' => $selectedSchedule,
            'publishReadiness' => $publishReadiness,
            'matchingMatrix' => $matchingMatrix,
            'settingSemester' => $settingSemester,
        ]);
    }

    public function edit(SectionExamSchedule $schedule, Request $request, ScheduleService $service): View
    {
        $schedule->load([
            'section.program',
            'slots.subject',
            'slots.room',
            'slots.proctors',
            'slots.matrixSlot.slotSubjects',
        ]);

        $rooms = Room::where('is_available', true)->orderBy('name')->get();
        $proctors = User::where('role', 'proctor')->where('status', 'active')->orderBy('last_name')->get();
        $subjectOptions = $this->sectionSubjectsForSchedule($schedule);
        $sectionStudentCount = $schedule->section->students()->count();

        $roomAvailabilityBySlot = [];
        $proctorAvailabilityBySlot = [];
        $matrixAssignedSubjectBySlot = [];
        $subjectOptionIds = collect($subjectOptions)->pluck('id')->map(fn ($id) => (int) $id);

        foreach ($schedule->slots as $slot) {
            $slotId = (int) $slot->id;
            $roomAvailability = $service->getUnavailableRoomIdsForSlot($slot, $sectionStudentCount);
            $proctorUnavailable = $service->getUnavailableProctorIdsForSlot($slot);

            if ($slot->room_id) {
                $selectedRoomId = (int) $slot->room_id;
                $roomAvailability['conflict'] = array_values(array_filter(
                    $roomAvailability['conflict'],
                    fn (int $roomId) => $roomId !== $selectedRoomId
                ));
                $roomAvailability['capacity'] = array_values(array_filter(
                    $roomAvailability['capacity'],
                    fn (int $roomId) => $roomId !== $selectedRoomId
                ));
            }

            $selectedProctorIds = $slot->proctors->pluck('id')->map(fn ($id) => (int) $id)->all();
            $proctorUnavailable = array_values(array_filter(
                $proctorUnavailable,
                fn (int $proctorId) => ! in_array($proctorId, $selectedProctorIds, true)
            ));

            $roomAvailabilityBySlot[$slotId] = $roomAvailability;
            $proctorAvailabilityBySlot[$slotId] = $proctorUnavailable;

            $matrixAssignedSubjectBySlot[$slotId] = $slot->matrixSlot
                ? $slot->matrixSlot->slotSubjects
                    ->pluck('subject_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $subjectId) => $subjectOptionIds->contains($subjectId))
                    ->values()
                    ->all()
                : [];
        }

        $filters = [
            'program_id' => (int) $schedule->program_id,
            'year_level' => (int) $schedule->section->year_level,
            'section_id' => (int) $schedule->section_id,
            'semester' => (int) $schedule->semester,
            'exam_period' => (string) $schedule->exam_period,
        ];

        $setting = AcademicSetting::current();

        return view('academic-head.schedules.edit', [
            'schedule' => $schedule,
            'rooms' => $rooms,
            'proctors' => $proctors,
            'subjectOptions' => $subjectOptions,
            'roomAvailabilityBySlot' => $roomAvailabilityBySlot,
            'proctorAvailabilityBySlot' => $proctorAvailabilityBySlot,
            'matrixAssignedSubjectBySlot' => $matrixAssignedSubjectBySlot,
            'filters' => $filters,
            'setting' => $setting,
            'returnQuery' => $request->query(),
        ]);
    }

    public function load(Request $request, ScheduleService $service): RedirectResponse
    {
        $setting = AcademicSetting::current();

        if (! $setting) {
            throw ValidationException::withMessages([
                'timeline' => 'Academic timeline is not configured yet. Ask the system admin to set it first.',
            ]);
        }

        $settingSemester = $this->normalizeSemester($setting->semester) ?? 1;

        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', 'integer', 'between:1,2'],
            'exam_period' => ['required', 'string', Rule::in(['Prelim', 'Midterm', 'Prefinals', 'Finals'])],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $section = Section::findOrFail((int) $validated['section_id']);

        if ((int) $section->program_id !== (int) $validated['program_id'] || (int) $section->year_level !== (int) $validated['year_level']) {
            throw ValidationException::withMessages([
                'section_id' => 'Selected section does not match the chosen program and year level.',
            ]);
        }

        if ($settingSemester === 1 && (int) $validated['semester'] === 2) {
            throw ValidationException::withMessages([
                'semester' => 'Second semester schedules are locked until the academic timeline is set to 2nd Semester.',
            ]);
        }

        $matrix = ExamMatrix::query()
            ->where('academic_year', $setting->academic_year)
            ->where('semester', (int) $validated['semester'])
            ->where('exam_period', $validated['exam_period'])
            ->where('status', 'uploaded')
            ->latest()
            ->first();

        if (! $matrix) {
            throw ValidationException::withMessages([
                'matrix' => 'No uploaded General Exam Matrix found for the selected context. Upload one from General Exam Matrix page first.',
            ]);
        }

        $existingSchedule = SectionExamSchedule::query()
            ->where('academic_year', $setting->academic_year)
            ->where('semester', (int) $validated['semester'])
            ->where('exam_period', $validated['exam_period'])
            ->where('section_id', $section->id)
            ->first();

        if (! $existingSchedule) {
            $service->generateForSection($matrix, $section, (int) $request->user()->id);
            $status = 'Schedule loaded successfully from uploaded matrix.';
        } else {
            $status = 'Schedule loaded successfully.';
        }

        return redirect()->route('academic-head.schedules.index', [
            'academic_year' => $setting->academic_year,
            'semester' => (int) $validated['semester'],
            'exam_period' => $validated['exam_period'],
            'program_id' => $section->program_id,
            'year_level' => $section->year_level,
            'section_id' => $section->id,
        ])->with('status', $status);
    }

    public function fetchMatrix(SectionExamSchedule $schedule, ScheduleService $service): RedirectResponse
    {
        $result = $service->refreshFromLatestMatrix($schedule);
        $schedule->loadMissing('section');

        $message = sprintf(
            'Fetched latest matrix updates: %d updated, %d added, %d removed.',
            (int) $result['updated'],
            (int) $result['created'],
            (int) $result['removed']
        );

        return redirect()->route('academic-head.schedules.index', [
            'academic_year' => $schedule->academic_year,
            'semester' => (int) $schedule->semester,
            'exam_period' => $schedule->exam_period,
            'program_id' => $schedule->program_id,
            'year_level' => (int) $schedule->section->year_level,
            'section_id' => $schedule->section_id,
        ])->with('status', $message);
    }

    public function generate(Request $request, ScheduleService $service): RedirectResponse
    {
        $setting = AcademicSetting::current();

        if (! $setting) {
            throw ValidationException::withMessages([
                'timeline' => 'Academic timeline is not configured yet. Ask the system admin to set it first.',
            ]);
        }

        $settingSemester = $this->normalizeSemester($setting->semester) ?? 1;

        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', 'integer', 'between:1,2'],
            'exam_period' => ['required', 'string', Rule::in(['Prelim', 'Midterm', 'Prefinals', 'Finals'])],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $section = Section::findOrFail((int) $validated['section_id']);

        if ((int) $section->program_id !== (int) $validated['program_id'] || (int) $section->year_level !== (int) $validated['year_level']) {
            throw ValidationException::withMessages([
                'section_id' => 'Selected section does not match the chosen program and year level.',
            ]);
        }

        if ($settingSemester === 1 && (int) $validated['semester'] === 2) {
            throw ValidationException::withMessages([
                'semester' => 'Second semester schedules are locked until the academic timeline is set to 2nd Semester.',
            ]);
        }

        $matrix = ExamMatrix::query()
            ->where('academic_year', $setting->academic_year)
            ->where('semester', (int) $validated['semester'])
            ->where('exam_period', $validated['exam_period'])
            ->where('status', 'uploaded')
            ->latest()
            ->first();

        if (! $matrix) {
            throw ValidationException::withMessages([
                'matrix' => 'No uploaded General Exam Matrix found for the selected context. Upload one from General Exam Matrix page first.',
            ]);
        }

        $service->generateForSection($matrix, $section, (int) $request->user()->id);

        return redirect()->route('academic-head.schedules.index', [
            'academic_year' => $matrix->academic_year,
            'semester' => $matrix->semester,
            'exam_period' => $matrix->exam_period,
            'program_id' => $section->program_id,
            'year_level' => $section->year_level,
            'section_id' => $section->id,
        ])->with('status', 'Draft schedule generated successfully.');
    }

    public function saveDraft(Request $request, SectionExamSchedule $schedule, ScheduleService $service): RedirectResponse
    {
        $validated = $request->validate([
            'slots' => ['nullable', 'array'],
            'slots.*.subject_id' => ['nullable', 'exists:subjects,id'],
            'slots.*.room_id' => ['nullable', 'exists:rooms,id'],
            'slots.*.proctor_ids' => ['nullable', 'array'],
            'slots.*.proctor_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', 'proctor')],
        ]);

        $service->saveDraftBatch($schedule, $validated['slots'] ?? []);

        return back()->with('status', 'Draft schedule saved successfully.');
    }

    public function upload(SectionExamSchedule $schedule, ScheduleService $service, Request $request): RedirectResponse
    {
        $service->publishSchedule($schedule, (int) $request->user()->id);

        return redirect()->route('academic-head.schedules.index', [
            'semester' => $schedule->semester,
            'exam_period' => $schedule->exam_period,
            'program_id' => $schedule->program_id,
            'year_level' => $schedule->section?->year_level,
            'section_id' => $schedule->section_id,
        ])->with('status', 'Schedule uploaded and published successfully.');
    }

    public function reset(SectionExamSchedule $schedule, ScheduleService $service): RedirectResponse
    {
        $service->reset($schedule);

        return back()->with('status', 'Schedule reset completed.');
    }

    public function destroy(SectionExamSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('status', 'Schedule deleted successfully.');
    }

    private function normalizeSemester(?string $semesterLabel): ?int
    {
        return match ($semesterLabel) {
            '1st Semester' => 1,
            '2nd Semester' => 2,
            default => null,
        };
    }

    private function sectionSubjectsForSchedule(SectionExamSchedule $schedule): array
    {
        $subjects = Subject::query()
            ->select('subjects.id', 'subjects.code', 'subjects.course_serial_number', 'subjects.name')
            ->join('program_subjects', 'subjects.id', '=', 'program_subjects.subject_id')
            ->where('program_subjects.program_id', (int) $schedule->program_id)
            ->where('program_subjects.year_level', (int) $schedule->section->year_level)
            ->where('program_subjects.semester', (int) $schedule->semester)
            ->orderBy('subjects.code')
            ->distinct()
            ->get();

        return $subjects->map(fn (Subject $subject): array => [
            'id' => (int) $subject->id,
            'code' => $subject->code,
            'course_serial_number' => $subject->course_serial_number,
            'name' => $subject->name,
        ])->all();
    }
}
