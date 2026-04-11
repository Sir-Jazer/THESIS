<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\SectionExamScheduleSlot;
use App\Models\SubjectExamReference;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $publishedAssignments = SectionExamScheduleSlot::query()
            ->with(['subject', 'room', 'schedule.section'])
            ->whereHas('schedule', function ($query): void {
                $query->where('status', 'published');
            })
            ->whereHas('proctors', function ($query) use ($user): void {
                $query->where('users.id', $user->id);
            })
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        $subjectIds = $publishedAssignments->pluck('subject_id')->filter()->unique()->values();
        $academicYears = $publishedAssignments->pluck('schedule.academic_year')->filter()->unique()->values();
        $semesters = $publishedAssignments->pluck('schedule.semester')->filter()->unique()->map(fn ($value) => (int) $value)->values();
        $examPeriods = $publishedAssignments->pluck('schedule.exam_period')->filter()->unique()->values();

        $referenceLookup = SubjectExamReference::query()
            ->when($subjectIds->isNotEmpty(), fn ($query) => $query->whereIn('subject_id', $subjectIds))
            ->when($academicYears->isNotEmpty(), fn ($query) => $query->whereIn('academic_year', $academicYears))
            ->when($semesters->isNotEmpty(), fn ($query) => $query->whereIn('semester', $semesters))
            ->when($examPeriods->isNotEmpty(), fn ($query) => $query->whereIn('exam_period', $examPeriods))
            ->get()
            ->keyBy(fn ($reference) => $reference->subject_id . '|' . $reference->academic_year . '|' . $reference->semester . '|' . $reference->exam_period);

        $publishedAssignments->each(function (SectionExamScheduleSlot $slot) use ($referenceLookup): void {
            $schedule = $slot->schedule;

            if (! $slot->subject_id || ! $schedule) {
                $slot->setAttribute('exam_reference_number', null);
                return;
            }

            $referenceKey = $slot->subject_id . '|' . $schedule->academic_year . '|' . $schedule->semester . '|' . $schedule->exam_period;
            $slot->setAttribute('exam_reference_number', $referenceLookup->get($referenceKey)?->exam_reference_number);
        });

        return view('proctor.dashboard', [
            'publishedAssignments' => $publishedAssignments,
        ]);
    }
}
