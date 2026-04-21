<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\ExamPermit;
use App\Models\StudentProfile;
use App\Services\Portal\ExamPortalService;
use Illuminate\View\View;

class AdviseeController extends Controller
{
    public function index(ExamPortalService $portalService): View
    {
        $user = auth()->user();
        $setting = $portalService->currentSetting();
        $semester = $portalService->normalizeSemester($setting?->semester);

        // Get advisory section IDs for this proctor
        $advisorySectionIds = $user->advisorySections()->pluck('id')->all();

        $advisees = StudentProfile::query()
            ->with(['user', 'section', 'program'])
            ->whereIn('section_id', $advisorySectionIds)
            ->orderBy('section_id')
            ->get();

        // Attach current permit status
        $advisees->each(function (StudentProfile $profile) use ($setting, $semester): void {
            $permit = null;

            if ($setting?->academic_year && $semester !== null && $setting?->exam_period) {
                $permit = ExamPermit::query()
                    ->where('student_profile_id', $profile->id)
                    ->where('academic_year', $setting->academic_year)
                    ->where('semester', $semester)
                    ->where('exam_period', $setting->exam_period)
                    ->where('is_active', true)
                    ->first();
            }

            $profile->current_permit = $permit;
        });

        // Group by section
        $adviseesBySection = $advisees->groupBy('section_id');

        return view('proctor.advisees.index', [
            'adviseesBySection' => $adviseesBySection,
            'setting' => $setting,
        ]);
    }
}
