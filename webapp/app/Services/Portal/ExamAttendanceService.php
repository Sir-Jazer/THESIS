<?php

namespace App\Services\Portal;

use App\Models\ExamAttendance;
use App\Models\ExamPermit;
use App\Models\SectionExamScheduleSlot;
use App\Models\User;

class ExamAttendanceService
{
    public function __construct(private readonly ExamPortalService $portalService)
    {
    }

    /**
     * Validate a QR token and log attendance for the given slot.
     *
     * Returns ['ok' => true, 'student_name' => string] on success.
     * Returns ['ok' => false, 'message' => string] on any validation failure.
     */
    public function previewAttendance(SectionExamScheduleSlot $slot, string $qrToken): array
    {
        $context = $this->resolveAttendanceContext($slot, $qrToken);

        if (! ($context['ok'] ?? false)) {
            return $context;
        }

        return [
            'ok' => true,
            'student_name' => $context['student_name'],
            'student_id' => $context['student_id'],
            'section_code' => $context['section_code'],
            'subject_code' => $context['subject_code'],
            'subject_name' => $context['subject_name'],
            'permit_message' => 'Permit is valid for the current exam period.',
        ];
    }

    public function logAttendance(SectionExamScheduleSlot $slot, string $qrToken, User $proctor): array
    {
        $context = $this->resolveAttendanceContext($slot, $qrToken);

        if (! ($context['ok'] ?? false)) {
            return $context;
        }

        /** @var \App\Models\ExamPermit $permit */
        $permit = $context['permit'];

        /** @var \App\Models\StudentProfile $studentProfile */
        $studentProfile = $context['student_profile'];

        // Log attendance
        ExamAttendance::create([
            'section_exam_schedule_slot_id' => $slot->id,
            'student_profile_id' => $studentProfile->id,
            'exam_permit_id' => $permit->id,
            'logged_by' => $proctor->id,
            'logged_at' => now(),
        ]);

        return [
            'ok' => true,
            'student_name' => $context['student_name'],
            'student_id' => $context['student_id'],
        ];
    }

    private function resolveAttendanceContext(SectionExamScheduleSlot $slot, string $qrToken): array
    {
        // 1. Active academic setting
        $setting = $this->portalService->currentSetting();
        $semester = $this->portalService->normalizeSemester($setting?->semester);

        if (! $setting?->academic_year || $semester === null || ! $setting?->exam_period) {
            return ['ok' => false, 'message' => 'Academic timeline is not configured.'];
        }

        // 2. Resolve permit by token
        $permit = ExamPermit::query()
            ->where('qr_token', $qrToken)
            ->first();

        if (! $permit) {
            return ['ok' => false, 'message' => 'Invalid QR code.'];
        }

        if (! $permit->is_active) {
            return ['ok' => false, 'message' => 'This exam permit has been revoked.'];
        }

        // 3. Permit period must match current setting
        if (
            $permit->academic_year !== $setting->academic_year
            || (int) $permit->semester !== $semester
            || $permit->exam_period !== $setting->exam_period
        ) {
            return ['ok' => false, 'message' => 'Permit is not valid for the current exam period.'];
        }

        // 4. Student's section must match slot's section
        $studentProfile = $permit->studentProfile;

        if (! $studentProfile) {
            return ['ok' => false, 'message' => 'Student profile not found for this permit.'];
        }

        $slot->loadMissing('schedule');

        if ((int) $studentProfile->section_id !== (int) $slot->schedule?->section_id) {
            return ['ok' => false, 'message' => 'Student is not enrolled in the section assigned to this slot.'];
        }

        // 5. Slot subject must be in the student's enrolled subjects
        if ($slot->subject_id) {
            $enrolledSubjectIds = $this->portalService->enrolledSubjectIdsForStudent(
                $studentProfile->user,
                $setting
            );

            if (! $enrolledSubjectIds->contains((int) $slot->subject_id)) {
                return ['ok' => false, 'message' => 'Student is not enrolled in the subject for this slot.'];
            }
        }

        // 6. Duplicate attendance check
        $alreadyLogged = ExamAttendance::query()
            ->where('section_exam_schedule_slot_id', $slot->id)
            ->where('student_profile_id', $studentProfile->id)
            ->exists();

        if ($alreadyLogged) {
            return ['ok' => false, 'message' => 'Attendance already recorded for this student in this slot.'];
        }

        $studentUser = $studentProfile->user;
        $studentName = trim(($studentUser?->first_name ?? '') . ' ' . ($studentUser?->last_name ?? ''));

        return [
            'ok' => true,
            'permit' => $permit,
            'student_profile' => $studentProfile,
            'student_name' => $studentName,
            'student_id' => $studentProfile->student_id,
            'section_code' => $slot->schedule?->section?->section_code,
            'subject_code' => $slot->subject?->code,
            'subject_name' => $slot->subject?->name,
        ];
    }
}
