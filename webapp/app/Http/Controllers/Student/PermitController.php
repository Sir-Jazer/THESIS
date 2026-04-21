<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Portal\ExamPermitService;
use App\Services\Portal\ExamPortalService;
use Illuminate\View\View;

class PermitController extends Controller
{
    public function show(ExamPortalService $portalService, ExamPermitService $permitService): View
    {
        $user = auth()->user();
        $setting = $portalService->currentSetting();
        $studentProfile = $user?->studentProfile;
        $permit = $studentProfile ? $portalService->activePermitForStudent($studentProfile, $setting) : null;

        return view('student.permit.show', [
            'setting' => $setting,
            'permit' => $permit,
            'qrPayload' => $permit ? $permitService->qrPayload($permit) : null,
        ]);
    }
}
