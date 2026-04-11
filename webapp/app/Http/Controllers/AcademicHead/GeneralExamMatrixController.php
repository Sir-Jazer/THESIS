<?php

namespace App\Http\Controllers\AcademicHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicSetting;
use App\Models\ExamMatrix;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View as ViewContract;
use Illuminate\View\View;

class GeneralExamMatrixController extends Controller
{
    private const EXAM_DAY_COUNT = 4;

    private const STANDARD_PERIODS = [
        [
            'label' => '7:00 AM - 8:30 AM',
            'start_time' => '07:00',
            'end_time' => '08:30',
            'session' => 'Morning',
        ],
        [
            'label' => '8:30 AM - 10:00 AM',
            'start_time' => '08:30',
            'end_time' => '10:00',
            'session' => 'Morning',
        ],
        [
            'label' => '10:00 AM - 11:30 AM',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'session' => 'Morning',
        ],
        [
            'label' => '11:30 AM - 1:00 PM',
            'start_time' => '11:30',
            'end_time' => '13:00',
            'session' => 'Afternoon',
        ],
        [
            'label' => '1:00 PM - 2:30 PM',
            'start_time' => '13:00',
            'end_time' => '14:30',
            'session' => 'Afternoon',
        ],
        [
            'label' => '2:30 PM - 4:00 PM',
            'start_time' => '14:30',
            'end_time' => '16:00',
            'session' => 'Afternoon',
        ],
        [
            'label' => '4:00 PM - 5:30 PM',
            'start_time' => '16:00',
            'end_time' => '17:30',
            'session' => 'Afternoon',
        ],
    ];

    public function index(): View
    {
        return view('academic-head.general-exam-matrix.index', [
            'matrices' => ExamMatrix::with([
                'slots.slotSubjects.subject:id,code,course_serial_number,name',
                'uploader:id,first_name,last_name',
            ])
                ->latest()
                ->paginate(15),
            'standardPeriods' => $this->standardPeriods(),
            'examDayCount' => self::EXAM_DAY_COUNT,
        ]);
    }

    public function create(): View
    {
        $setting = AcademicSetting::current();

        return view('academic-head.general-exam-matrix.create', [
            'setting' => $setting,
            'subjects' => Subject::orderBy('code')->get(['id', 'code', 'course_serial_number', 'name']),
            'standardPeriods' => $this->standardPeriods(),
            'examDayCount' => self::EXAM_DAY_COUNT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($validated, $request): void {
            $matrix = ExamMatrix::create([
                'academic_year' => $validated['academic_year'],
                'semester' => (int) $validated['semester'],
                'exam_period' => $validated['exam_period'],
                'program_id' => null,
                'name' => $validated['name'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $this->syncSlots($matrix, $validated['slots']);
        });

        return redirect()->route('academic-head.general-exam-matrix.index')->with('status', 'General exam matrix created successfully.');
    }

    public function edit(ExamMatrix $matrix): ViewContract
    {
        $matrix->load(['slots.slotSubjects.subject']);
        $setting = AcademicSetting::current();

        return view('academic-head.general-exam-matrix.edit', [
            'matrix' => $matrix,
            'setting' => $setting,
            'subjects' => Subject::orderBy('code')->get(['id', 'code', 'course_serial_number', 'name']),
            'standardPeriods' => $this->standardPeriods(),
            'examDayCount' => self::EXAM_DAY_COUNT,
        ]);
    }

    public function update(Request $request, ExamMatrix $matrix): RedirectResponse
    {
        $validated = $this->validatePayload($request, $matrix->id);

        DB::transaction(function () use ($matrix, $validated): void {
            $matrix->update([
                'academic_year' => $validated['academic_year'],
                'semester' => (int) $validated['semester'],
                'exam_period' => $validated['exam_period'],
                'program_id' => null,
                'name' => $validated['name'] ?? null,
            ]);

            $this->syncSlots($matrix, $validated['slots']);
        });

        return redirect()->route('academic-head.general-exam-matrix.index')->with('status', 'General exam matrix updated successfully.');
    }

    public function destroy(ExamMatrix $matrix): RedirectResponse
    {
        $matrix->delete();

        return redirect()->route('academic-head.general-exam-matrix.index')->with('status', 'General exam matrix deleted successfully.');
    }

    public function upload(Request $request, ExamMatrix $matrix): RedirectResponse
    {
        DB::transaction(function () use ($request, $matrix): void {
            ExamMatrix::query()
                ->where('id', '!=', $matrix->id)
                ->where('academic_year', $matrix->academic_year)
                ->where('semester', (int) $matrix->semester)
                ->where('exam_period', $matrix->exam_period)
                ->where('status', 'uploaded')
                ->update([
                    'status' => 'draft',
                    'uploaded_at' => null,
                    'uploaded_by' => null,
                ]);

            $matrix->update([
                'status' => 'uploaded',
                'uploaded_at' => now(),
                'uploaded_by' => $request->user()?->id,
            ]);
        });

        return redirect()->route('academic-head.general-exam-matrix.index')->with('status', 'General exam matrix uploaded successfully. It is now available for schedule plotting.');
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $setting = AcademicSetting::current();
        $settingSemester = $this->normalizeSemester($setting?->semester);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['required', 'integer', 'between:1,2'],
            'exam_period' => ['required', 'string', Rule::in(['Prelim', 'Midterm', 'Prefinals', 'Finals'])],
            'exam_days' => ['required', 'array', 'size:' . self::EXAM_DAY_COUNT],
            'exam_days.*.date' => ['required', 'date'],
            'exam_days.*.periods' => ['required', 'array', 'size:' . count(self::STANDARD_PERIODS)],
            'exam_days.*.periods.*.subject_ids' => ['nullable', 'array'],
            'exam_days.*.periods.*.subject_ids.*' => ['nullable', 'exists:subjects,id'],
        ], [
            'exam_days.required' => 'Provide the four exam dates for this matrix.',
            'exam_days.size' => 'The matrix must cover exactly four exam days.',
            'exam_days.*.date.required' => 'Each exam day needs a date.',
            'exam_days.*.periods.size' => 'Each exam day must include all standard time periods.',
        ]);

        if ($setting !== null) {
            $validated['academic_year'] = $setting->academic_year;
        } elseif (! isset($validated['academic_year']) || trim((string) $validated['academic_year']) === '') {
            throw ValidationException::withMessages([
                'academic_year' => 'Academic year is not configured yet. Please ask the system admin to set the academic timeline.',
            ]);
        }

        if ($settingSemester === 1 && (int) $validated['semester'] === 2) {
            throw ValidationException::withMessages([
                'semester' => 'Second semester matrices are locked until the academic timeline is set to 2nd Semester.',
            ]);
        }

        $duplicateMatrixExists = ExamMatrix::query()
            ->where('academic_year', $validated['academic_year'])
            ->where('semester', (int) $validated['semester'])
            ->where('exam_period', $validated['exam_period'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicateMatrixExists) {
            throw ValidationException::withMessages([
                'exam_period' => 'A general exam matrix already exists for this academic year, semester, and exam period.',
            ]);
        }

        $dates = collect($validated['exam_days'])->pluck('date')->all();

        if (count($dates) !== count(array_unique($dates))) {
            throw ValidationException::withMessages([
                'exam_days' => 'Exam dates must be unique across the four-day matrix.',
            ]);
        }

        $sortedDates = $dates;
        sort($sortedDates);

        if ($sortedDates !== $dates) {
            throw ValidationException::withMessages([
                'exam_days' => 'Arrange the exam dates in chronological order from Day 1 to Day 4.',
            ]);
        }

        foreach (($validated['exam_days'] ?? []) as $dayIndex => $examDay) {
            foreach (($examDay['periods'] ?? []) as $periodIndex => $period) {
                $subjectIds = collect($period['subject_ids'] ?? [])
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->map(fn ($value) => (int) $value)
                    ->values();

                if ($subjectIds->count() !== $subjectIds->unique()->count()) {
                    throw ValidationException::withMessages([
                        "exam_days.$dayIndex.periods.$periodIndex.subject_ids" => 'Duplicate subjects are not allowed in the same time slot.',
                    ]);
                }
            }
        }

        $validated['slots'] = collect($validated['exam_days'])
            ->values()
            ->flatMap(function (array $examDay, int $dayIndex): array {
                return collect(self::STANDARD_PERIODS)
                    ->map(function (array $period, int $periodIndex) use ($examDay, $dayIndex): array {
                        $subjectIds = collect($examDay['periods'][$periodIndex]['subject_ids'] ?? [])
                            ->filter(fn ($value) => $value !== null && $value !== '')
                            ->map(fn ($value) => (int) $value)
                            ->unique()
                            ->values()
                            ->all();

                        return [
                            'slot_date' => $examDay['date'],
                            'start_time' => $period['start_time'],
                            'end_time' => $period['end_time'],
                            'is_fixed' => count($subjectIds) > 0,
                            'subject_ids' => $subjectIds,
                            'sort_order' => ($dayIndex * count(self::STANDARD_PERIODS)) + $periodIndex,
                        ];
                    })
                    ->all();
            })
            ->all();

        return $validated;
    }

    private function syncSlots(ExamMatrix $matrix, array $slots): void
    {
        $matrix->slots()->delete();

        foreach (collect($slots)->values() as $index => $row) {
            $slot = $matrix->slots()->create([
                'slot_date' => $row['slot_date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'is_fixed' => (bool) ($row['is_fixed'] ?? false),
                'sort_order' => $row['sort_order'] ?? $index,
            ]);

            $subjectPayload = collect($row['subject_ids'] ?? [])
                ->values()
                ->map(fn (int $subjectId, int $subjectIndex): array => [
                    'subject_id' => $subjectId,
                    'sort_order' => $subjectIndex,
                ])
                ->all();

            if ($subjectPayload !== []) {
                $slot->slotSubjects()->createMany($subjectPayload);
            }
        }
    }

    private function standardPeriods(): array
    {
        return self::STANDARD_PERIODS;
    }

    private function normalizeSemester(?string $semesterLabel): ?int
    {
        return match ($semesterLabel) {
            '1st Semester' => 1,
            '2nd Semester' => 2,
            default => null,
        };
    }
}

