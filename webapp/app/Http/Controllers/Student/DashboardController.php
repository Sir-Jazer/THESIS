<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Portal\ExamPortalService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ExamPortalService $portalService): View
    {
        $user = auth()->user();
        $setting = $portalService->currentSetting();
        $selectedPeriod = $portalService->resolvePeriod($setting?->exam_period, $setting);
        $subjectRows = $portalService->studentScheduleRows($user, $selectedPeriod, $setting);
        $studentProfile = $user?->studentProfile;

        return view('student.dashboard', [
            'setting' => $setting,
            'selectedPeriod' => $selectedPeriod,
            'subjectRows' => $subjectRows,
            'currentPermit' => $studentProfile ? $portalService->activePermitForStudent($studentProfile, $setting) : null,
        ]);
    }
}
