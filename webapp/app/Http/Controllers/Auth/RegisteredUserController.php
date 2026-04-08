<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function createRegular(): View
    {
        return view('auth.register-regular', [
            'programs' => Program::orderBy('code')->get(),
            'sections' => Section::with('program')->orderBy('section_code')->get(),
        ]);
    }

    public function storeRegular(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'student_id' => ['required', 'string', 'max:50', 'unique:student_profiles,student_id'],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,6'],
            'section_id' => ['nullable', 'exists:sections,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'student',
                'status' => 'pending',
            ]);

            StudentProfile::create([
                'user_id' => $user->id,
                'student_id' => $validated['student_id'],
                'program_id' => (int) $validated['program_id'],
                'year_level' => (int) $validated['year_level'],
                'section_id' => $validated['section_id'] ?? null,
            ]);
        });

        return redirect()->route('login')->with('status', 'Registration submitted. Your account is pending approval.');
    }

    public function createIrregularStepOne(): View
    {
        return view('auth.register-irregular-step1', [
            'programs' => Program::orderBy('code')->get(),
        ]);
    }

    public function storeIrregularStepOne(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'student_id' => ['required', 'string', 'max:50', 'unique:student_profiles,student_id'],
            'program_id' => ['required', 'exists:programs,id'],
        ]);

        $request->session()->put('irregular_registration', $validated);

        return redirect()->route('register.irregular.step2');
    }

    public function createIrregularStepTwo(Request $request): View|RedirectResponse
    {
        $stepOneData = $request->session()->get('irregular_registration');

        if (! $stepOneData) {
            return redirect()->route('register.irregular')
                ->withErrors(['email' => 'Please complete step 1 first.']);
        }

        $program = Program::with(['subjects' => function ($query) {
            $query->orderBy('program_subjects.year_level')->orderBy('subjects.code');
        }])->findOrFail($stepOneData['program_id']);

        $subjectsByYear = $program->subjects->groupBy(fn ($subject) => (int) $subject->pivot->year_level);

        return view('auth.register-irregular-step2', [
            'program' => $program,
            'subjectsByYear' => $subjectsByYear,
        ]);
    }

    public function storeIrregularStepTwo(Request $request): RedirectResponse
    {
        $stepOneData = $request->session()->get('irregular_registration');

        if (! $stepOneData) {
            return redirect()->route('register.irregular')
                ->withErrors(['email' => 'Please complete step 1 first.']);
        }

        $validated = $request->validate([
            'selected_subject_ids' => ['required', 'array', 'min:1'],
            'selected_subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        $subjectIds = array_values(array_unique(array_map('intval', $validated['selected_subject_ids'])));
        $programId = (int) $stepOneData['program_id'];

        $yearLevelCounts = DB::table('program_subjects')
            ->select('year_level', DB::raw('COUNT(*) as total'))
            ->where('program_id', $programId)
            ->whereIn('subject_id', $subjectIds)
            ->groupBy('year_level')
            ->orderByDesc('total')
            ->orderBy('year_level')
            ->get();

        $computedYearLevel = (int) optional($yearLevelCounts->first())->year_level;

        if ($computedYearLevel < 1) {
            return back()->withErrors([
                'selected_subject_ids' => 'Selected subjects do not match the chosen program.',
            ])->withInput();
        }

        DB::transaction(function () use ($stepOneData, $subjectIds, $programId, $computedYearLevel, $request) {
            $user = User::create([
                'first_name' => $stepOneData['first_name'],
                'last_name' => $stepOneData['last_name'],
                'email' => $stepOneData['email'],
                'password' => $stepOneData['password'],
                'role' => 'student',
                'status' => 'pending',
            ]);

            $section = Section::query()
                ->where('program_id', $programId)
                ->where('year_level', $computedYearLevel)
                ->orderBy('section_code')
                ->first();

            StudentProfile::create([
                'user_id' => $user->id,
                'student_id' => $stepOneData['student_id'],
                'program_id' => $programId,
                'year_level' => $computedYearLevel,
                'section_id' => $section?->id,
            ]);

            $user->subjects()->attach($subjectIds);
            $request->session()->forget('irregular_registration');
        });

        return redirect()->route('login')->with('status', 'Registration submitted. Your account is pending approval.');
    }
}
