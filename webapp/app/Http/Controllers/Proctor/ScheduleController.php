<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\SectionExamSchedule;
use App\Models\SectionExamScheduleSlot;
use App\Services\Portal\ExamPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request, ExamPortalService $portalService): View
    {
        $user = auth()->user();
        $setting = $portalService->currentSetting();
        $selectedPeriod = $portalService->resolvePeriod($request->query('period'), $setting);

        // Load schedule slots where this proctor is assigned, filtered by period
        $slots = SectionExamScheduleSlot::query()
            ->with([
                'subject:id,code,name',
                'room:id,name',
                'schedule.section:id,section_code,program_id,year_level',
                'schedule.section.program:id,code',
            ])
            ->whereHas('schedule', function ($query) use ($selectedPeriod, $setting): void {
                $query->where('status', 'published')
                    ->where('exam_period', $selectedPeriod);

                if ($setting?->academic_year) {
                    $query->where('academic_year', $setting->academic_year);
                }
            })
            ->whereHas('proctors', function ($query) use ($user): void {
                $query->where('users.id', $user->id);
            })
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        // Group by schedule (section)
        $slotsBySchedule = $slots->groupBy('section_exam_schedule_id');

        // Load schedules for the grouped result
        $scheduleIds = $slotsBySchedule->keys()->all();
        $schedules = SectionExamSchedule::query()
            ->with(['section.program'])
            ->whereIn('id', $scheduleIds)
            ->get()
            ->keyBy('id');

        return view('proctor.schedules.index', [
            'setting' => $setting,
            'selectedPeriod' => $selectedPeriod,
            'periods' => ExamPortalService::PERIODS,
            'slotsBySchedule' => $slotsBySchedule,
            'schedules' => $schedules,
        ]);
    }

    public function attendance(SectionExamScheduleSlot $slot): View
    {
        $user = auth()->user();

        // Verify proctor is assigned to this slot
        $isAssigned = $slot->proctors()->where('users.id', $user->id)->exists();

        if (! $isAssigned) {
            abort(403, 'You are not assigned to this exam slot.');
        }

        $slot->loadMissing([
            'subject:id,code,name',
            'room:id,name',
            'schedule.section:id,section_code',
            'attendances.studentProfile.user',
            'attendances.logger:id,first_name,last_name',
        ]);

        $attendances = $slot->attendances->sortBy('logged_at');

        return view('proctor.schedules.attendance', [
            'slot' => $slot,
            'attendances' => $attendances,
        ]);
    }
}
