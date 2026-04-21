<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\StudentProfile;
use App\Services\Portal\ExamPermitService;
use App\Services\Portal\ExamPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPaymentController extends Controller
{
    public function index(Request $request, ExamPortalService $portalService): View
    {
        $filters = [
            'search' => trim((string) $request->string('search')),
            'program_id' => $request->integer('program_id') ?: null,
            'year_level' => $request->integer('year_level') ?: null,
        ];

        return view('cashier.student-payments.index', [
            'setting' => $portalService->currentSetting(),
            'filters' => $filters,
            'programs' => Program::orderBy('code')->get(['id', 'code', 'name']),
            'students' => $portalService->cashierStudentRows($filters),
        ]);
    }

    public function generate(Request $request, StudentProfile $studentProfile, ExamPermitService $permitService): RedirectResponse
    {
        $permitService->generateForCurrentPeriod($studentProfile, $request->user());

        return back()->with('status', 'Exam permit generated successfully.');
    }

    public function revoke(StudentProfile $studentProfile, ExamPermitService $permitService): RedirectResponse
    {
        $permitService->revokeForCurrentPeriod($studentProfile);

        return back()->with('status', 'Exam permit revoked successfully.');
    }
}
