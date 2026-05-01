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
        $user = $request->user()?->loadMissing('studentProfile.section.proctor');
        $setting = $portalService->currentSetting();
        $selectedPeriod = $portalService->resolvePeriod($request->string('period')->toString() ?: null, $setting);
        $adviserName = $user?->studentProfile?->section?->proctor?->full_name ?? 'TBA';

        return view('student.subjects.index', [
            'setting' => $setting,
            'periods' => ExamPortalService::PERIODS,
            'selectedPeriod' => $selectedPeriod,
            'adviserName' => $adviserName,
            'subjectRows' => $portalService->studentScheduleRows($user, $selectedPeriod, $setting),
        ]);
    }
}
