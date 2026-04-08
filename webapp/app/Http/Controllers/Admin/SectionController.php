<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(Program $program): View
    {
        return view('admin.programs.sections.index', [
            'program' => $program,
            'sections' => $program->sections()
                ->withCount('students')
                ->orderBy('year_level')
                ->orderBy('section_code')
                ->paginate(15),
        ]);
    }

    public function create(Program $program): View
    {
        return view('admin.programs.sections.create', [
            'program' => $program,
        ]);
    }

    public function store(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'section_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sections', 'section_code')->where(function ($query) use ($program, $request) {
                    return $query
                        ->where('program_id', $program->id)
                        ->where('year_level', $request->integer('year_level'));
                }),
            ],
        ], [
            'section_code.unique' => 'That section code already exists for this program and year level.',
        ]);

        $program->sections()->create($validated);

        return redirect()
            ->route('admin.programs.sections.index', $program)
            ->with('status', 'Section created successfully.');
    }

    public function edit(Program $program, Section $section): View
    {
        $this->ensureSectionBelongsToProgram($program, $section);

        return view('admin.programs.sections.edit', [
            'program' => $program,
            'section' => $section,
        ]);
    }

    public function update(Request $request, Program $program, Section $section): RedirectResponse
    {
        $this->ensureSectionBelongsToProgram($program, $section);

        $validated = $request->validate([
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'section_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sections', 'section_code')
                    ->ignore($section->id)
                    ->where(function ($query) use ($program, $request) {
                        return $query
                            ->where('program_id', $program->id)
                            ->where('year_level', $request->integer('year_level'));
                    }),
            ],
        ], [
            'section_code.unique' => 'That section code already exists for this program and year level.',
        ]);

        $section->update($validated);

        return redirect()
            ->route('admin.programs.sections.index', $program)
            ->with('status', 'Section updated successfully.');
    }

    public function destroy(Program $program, Section $section): RedirectResponse
    {
        $this->ensureSectionBelongsToProgram($program, $section);

        if ($section->students()->exists()) {
            return redirect()
                ->route('admin.programs.sections.index', $program)
                ->withErrors(['section' => 'Cannot delete this section because students are still assigned to it.']);
        }

        $section->delete();

        return redirect()
            ->route('admin.programs.sections.index', $program)
            ->with('status', 'Section deleted successfully.');
    }

    protected function ensureSectionBelongsToProgram(Program $program, Section $section): void
    {
        abort_if($section->program_id !== $program->id, 404);
    }
}
