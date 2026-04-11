<?php

namespace App\Http\Controllers\AcademicHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicSetting;
use App\Models\Program;
use App\Models\Subject;
use App\Models\SubjectExamReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectExamReferenceController extends Controller
{
    public function index(Request $request): View
    {
        $currentSetting = AcademicSetting::current();

        $defaultAcademicYear = $currentSetting?->academic_year ?? now()->year . '-' . (now()->year + 1);
        $defaultSemester = $this->normalizeSemester($currentSetting?->semester) ?? 1;
        $defaultExamPeriod = $currentSetting?->exam_period ?? 'Prelim';

        $filters = [
            'academic_year' => $request->string('academic_year')->toString() ?: $defaultAcademicYear,
            'semester' => (int) ($request->integer('semester') ?: $defaultSemester),
            'exam_period' => $request->string('exam_period')->toString() ?: $defaultExamPeriod,
            'program_id' => $request->integer('program_id') ?: null,
        ];

        $subjects = Subject::query()
            ->with([
                'programs:id,code',
                'examReferences' => function ($query) use ($filters): void {
                    $query->where('academic_year', $filters['academic_year'])
                        ->where('semester', $filters['semester'])
                        ->where('exam_period', $filters['exam_period']);
                },
            ])
            ->when($filters['program_id'], function ($query, $programId): void {
                $query->whereHas('programs', fn ($inner) => $inner->where('program_id', (int) $programId));
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('academic-head.subject-exam-references.index', [
            'subjects' => $subjects,
            'programs' => Program::orderBy('code')->get(['id', 'code', 'name']),
            'filters' => $filters,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'integer', 'between:1,2'],
            'exam_period' => ['required', 'string', Rule::in(['Prelim', 'Midterm', 'Prefinals', 'Finals'])],
            'program_id' => ['nullable', 'exists:programs,id'],
            'references' => ['nullable', 'array'],
            'references.*' => ['nullable', 'string', 'max:50'],
        ]);

        $references = $validated['references'] ?? [];
        $subjectIds = collect(array_keys($references))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($subjectIds->isNotEmpty()) {
            $knownSubjectCount = Subject::whereIn('id', $subjectIds)->count();
            if ($knownSubjectCount !== $subjectIds->count()) {
                return back()->withErrors(['references' => 'One or more subject rows are invalid.'])->withInput();
            }
        }

        foreach ($references as $subjectId => $examReferenceNumber) {
            $subjectId = (int) $subjectId;
            $examReferenceNumber = trim((string) $examReferenceNumber);

            if ($subjectId <= 0) {
                continue;
            }

            $scopeQuery = SubjectExamReference::query()
                ->where('subject_id', $subjectId)
                ->where('academic_year', $validated['academic_year'])
                ->where('semester', (int) $validated['semester'])
                ->where('exam_period', $validated['exam_period']);

            if ($examReferenceNumber === '') {
                $scopeQuery->delete();
                continue;
            }

            SubjectExamReference::updateOrCreate([
                'subject_id' => $subjectId,
                'academic_year' => $validated['academic_year'],
                'semester' => (int) $validated['semester'],
                'exam_period' => $validated['exam_period'],
            ], [
                'exam_reference_number' => $examReferenceNumber,
            ]);
        }

        return redirect()->route('academic-head.subject-exam-references.index', [
            'academic_year' => $validated['academic_year'],
            'semester' => (int) $validated['semester'],
            'exam_period' => $validated['exam_period'],
            'program_id' => $validated['program_id'] ?? null,
        ])->with('status', 'Exam reference numbers updated successfully.');
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
