<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ExamPermit;
use App\Models\User;
use App\Services\Portal\ExamPortalService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ExamPortalService $portalService): View
    {
        $setting = $portalService->currentSetting();
        $semester = $portalService->normalizeSemester($setting?->semester);
        $issuedPermits = 0;

        if ($setting?->academic_year && $semester !== null && $setting?->exam_period) {
            $issuedPermits = ExamPermit::query()
                ->where('academic_year', $setting->academic_year)
                ->where('semester', $semester)
                ->where('exam_period', $setting->exam_period)
                ->where('is_active', true)
                ->count();
        }

        $studentCount = User::query()
            ->where('role', 'student')
            ->whereHas('studentProfile')
            ->count();

        return view('cashier.dashboard', [
            'setting' => $setting,
            'studentCount' => $studentCount,
            'issuedPermitCount' => $issuedPermits,
            'pendingPermitCount' => max($studentCount - $issuedPermits, 0),
        ]);
    }
}
