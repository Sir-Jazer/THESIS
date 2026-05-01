<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'program_id' => $request->integer('program_id') ?: null,
            'year_level' => in_array($request->integer('year_level'), [1, 2, 3, 4], true)
                ? $request->integer('year_level')
                : null,
            'semester' => in_array($request->integer('semester'), [1, 2], true)
                ? $request->integer('semester')
                : null,
        ];

        $subjects = Subject::query()
            ->with(['programs:id,code'])
            ->withCount('programs')
            ->when($filters['program_id'] || $filters['year_level'] || $filters['semester'], function ($query) use ($filters): void {
                $query->whereHas('programs', function ($programQuery) use ($filters): void {
                    if ($filters['program_id']) {
                        $programQuery->where('programs.id', $filters['program_id']);
                    }

                    if ($filters['year_level']) {
                        $programQuery->where('program_subjects.year_level', '=', $filters['year_level']);
                    }

                    if ($filters['semester']) {
                        $programQuery->where('program_subjects.semester', '=', $filters['semester']);
                    }
                });
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
            'programs' => Program::orderBy('code')->get(['id', 'code', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.subjects.create', [
            'programs' => Program::orderBy('code')->get(),
            'subjects' => Subject::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSubjectRequest($request);

        DB::transaction(function () use ($validated): void {
            $subject = Subject::create([
                'code' => $validated['code'],
                'course_serial_number' => $validated['course_serial_number'],
                'name' => $validated['name'],
                'units' => $validated['units'],
            ]);

            $this->syncProgramLinks($subject, $validated['program_links'] ?? []);
            $subject->prerequisites()->sync($validated['prerequisite_ids'] ?? []);
            $subject->corequisites()->sync($validated['corequisite_ids'] ?? []);
        });

        return redirect()->route('admin.subjects.index')->with('status', 'Subject created successfully.');
    }

    public function edit(Subject $subject): View
    {
        $subject->load(['programs', 'prerequisites', 'corequisites']);

        return view('admin.subjects.edit', [
            'subject' => $subject,
            'programs' => Program::orderBy('code')->get(),
            'subjects' => Subject::where('id', '!=', $subject->id)->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $this->validateSubjectRequest($request, $subject->id);

        DB::transaction(function () use ($subject, $validated): void {
            $subject->update([
                'code' => $validated['code'],
                'course_serial_number' => $validated['course_serial_number'],
                'name' => $validated['name'],
                'units' => $validated['units'],
            ]);

            $this->syncProgramLinks($subject, $validated['program_links'] ?? []);
            $subject->prerequisites()->sync($validated['prerequisite_ids'] ?? []);
            $subject->corequisites()->sync($validated['corequisite_ids'] ?? []);
        });

        return redirect()->route('admin.subjects.index')->with('status', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('status', 'Subject removed successfully.');
    }

    private function validateSubjectRequest(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = 'unique:subjects,code';
        $serialRule = 'unique:subjects,course_serial_number';
        if ($ignoreId) {
            $codeRule .= ',' . $ignoreId;
            $serialRule .= ',' . $ignoreId;
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:50', $codeRule],
            'course_serial_number' => ['required', 'string', 'max:50', $serialRule],
            'name' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:1', 'max:10'],
            'program_links' => ['nullable', 'array'],
            'program_links.*.program_id' => ['required', 'distinct', 'exists:programs,id'],
            'program_links.*.year_level' => ['required', 'integer', 'between:1,4'],
            'program_links.*.semester' => ['required', 'integer', 'between:1,2'],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['integer', 'exists:subjects,id'],
            'corequisite_ids' => ['nullable', 'array'],
            'corequisite_ids.*' => ['integer', 'exists:subjects,id'],
        ], [
            'code.unique' => 'This subject code already exists. Edit the existing subject and add another Program Association instead of creating a duplicate subject.',
            'course_serial_number.unique' => 'This course serial number already exists. Edit the existing subject and add another Program Association instead of creating a duplicate subject.',
            'program_links.*.program_id.distinct' => 'Each program may only appear once in Program Associations for this subject record.',
            'program_links.*.program_id.required' => 'Select a program for each association row.',
            'program_links.*.year_level.required' => 'Select a year level for each program association.',
            'program_links.*.semester.required' => 'Select a semester for each program association.',
        ]);
    }

    private function syncProgramLinks(Subject $subject, array $programLinks): void
    {
        $payload = [];

        foreach ($programLinks as $row) {
            if (empty($row['program_id'])) {
                continue;
            }

            $payload[(int) $row['program_id']] = [
                'year_level' => (int) $row['year_level'],
                'semester' => (int) $row['semester'],
            ];
        }

        $subject->programs()->sync($payload);
    }
}
