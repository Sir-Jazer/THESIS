<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Portal\ExamPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request, ExamPortalService $portalService): View
    {
        $setting = $portalService->currentSetting();
        $selectedPeriod = $portalService->resolvePeriod($request->string('period')->toString() ?: null, $setting);

        return view('student.subjects.index', [
            'setting' => $setting,
            'periods' => ExamPortalService::PERIODS,
            'selectedPeriod' => $selectedPeriod,
            'subjectRows' => $portalService->studentScheduleRows($request->user(), $selectedPeriod, $setting),
        ]);
    }
}
