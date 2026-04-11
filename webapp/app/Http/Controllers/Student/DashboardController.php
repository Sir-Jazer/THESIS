<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SectionExamScheduleSlot;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $sectionId = $user?->studentProfile?->section_id;

        $publishedSlots = collect();
        if ($sectionId) {
            $publishedSlots = SectionExamScheduleSlot::query()
                ->with(['subject', 'room', 'schedule.section'])
                ->whereHas('schedule', function ($query) use ($sectionId): void {
                    $query->where('status', 'published')
                        ->where('section_id', $sectionId);
                })
                ->orderBy('slot_date')
                ->orderBy('start_time')
                ->get();
        }

        return view('student.dashboard', [
            'publishedSlots' => $publishedSlots,
        ]);
    }
}
