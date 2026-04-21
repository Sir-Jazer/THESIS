<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\SectionExamScheduleSlot;
use App\Services\Portal\ExamAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();

        // Sections where this proctor is assigned to at least one published slot
        $slots = SectionExamScheduleSlot::query()
            ->with(['subject:id,code,name', 'room:id,name', 'schedule.section:id,section_code,program_id,year_level'])
            ->whereHas('schedule', function ($query): void {
                $query->where('status', 'published');
            })
            ->whereHas('proctors', function ($query) use ($user): void {
                $query->where('users.id', $user->id);
            })
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        // Group sections (unique) for the section dropdown
        $sections = $slots
            ->map(fn ($slot) => $slot->schedule?->section)
            ->filter()
            ->unique('id')
            ->values();

        // Build slot list for JS filtering keyed by section id
        $slotsBySection = $slots->groupBy(fn ($slot) => $slot->schedule?->section_id);

        $slotOptions = $slotsBySection->map(fn ($sectionSlots) => $sectionSlots->map(fn ($slot) => [
            'id' => $slot->id,
            'label' => optional($slot->slot_date)->format('Y-m-d')
                . ' ' . substr((string) $slot->start_time, 0, 5)
                . '-' . substr((string) $slot->end_time, 0, 5)
                . ' — ' . ($slot->subject?->code ?? 'TBA'),
        ])->values())->all();

        return view('proctor.scanner.show', [
            'sections' => $sections,
            'slotOptions' => $slotOptions,
        ]);
    }

    public function scan(Request $request, ExamAttendanceService $attendanceService): JsonResponse
    {
        $validated = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:section_exam_schedule_slots,id'],
            'qr_token' => ['required', 'string', 'max:255'],
        ]);

        $user = auth()->user();

        $slot = SectionExamScheduleSlot::query()
            ->with(['schedule', 'subject'])
            ->findOrFail($validated['slot_id']);

        // Verify this proctor is assigned to the slot
        $isAssigned = $slot->proctors()->where('users.id', $user->id)->exists();

        if (! $isAssigned) {
            return response()->json(['ok' => false, 'message' => 'You are not assigned to this slot.'], 403);
        }

        $result = $attendanceService->logAttendance($slot, $validated['qr_token'], $user);

        return response()->json($result);
    }
}
