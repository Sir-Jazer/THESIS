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
    public function index(): View
    {
        return view('admin.subjects.index', [
            'subjects' => Subject::withCount('programs')->orderBy('code')->paginate(15),
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
        if ($ignoreId) {
            $codeRule .= ',' . $ignoreId;
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:50', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:1', 'max:10'],
            'program_links' => ['nullable', 'array'],
            'program_links.*.program_id' => ['required', 'exists:programs,id'],
            'program_links.*.year_level' => ['required', 'integer', 'between:1,6'],
            'program_links.*.semester' => ['required', 'integer', 'between:1,2'],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['integer', 'exists:subjects,id'],
            'corequisite_ids' => ['nullable', 'array'],
            'corequisite_ids.*' => ['integer', 'exists:subjects,id'],
        ]);
    }

    private function syncProgramLinks(Subject $subject, array $programLinks): void
    {
        $payload = [];

        foreach ($programLinks as $row) {
            $payload[(int) $row['program_id']] = [
                'year_level' => (int) $row['year_level'],
                'semester' => (int) $row['semester'],
            ];
        }

        $subject->programs()->sync($payload);
    }
}
