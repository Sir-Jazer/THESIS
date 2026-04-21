<?php

namespace App\Services\Portal;

use App\Models\ExamPermit;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Str;

class ExamPermitService
{
    public function __construct(private readonly ExamPortalService $portalService)
    {
    }

    public function generateForCurrentPeriod(StudentProfile $studentProfile, User $cashier): ExamPermit
    {
        $setting = $this->portalService->currentSetting();
        $semester = $this->portalService->normalizeSemester($setting?->semester);

        if (! $setting?->academic_year || $semester === null || ! $setting?->exam_period) {
            abort(422, 'Academic timeline is not configured.');
        }

        $permit = ExamPermit::query()->firstOrNew([
            'student_profile_id' => $studentProfile->id,
            'academic_year' => $setting->academic_year,
            'semester' => $semester,
            'exam_period' => $setting->exam_period,
        ]);

        $permit->fill([
            'qr_token' => Str::uuid()->toString(),
            'generated_by' => $cashier->id,
            'generated_at' => now(),
            'revoked_at' => null,
            'is_active' => true,
        ]);
        $permit->save();

        return $permit->fresh();
    }

    public function revokeForCurrentPeriod(StudentProfile $studentProfile): void
    {
        $setting = $this->portalService->currentSetting();
        $semester = $this->portalService->normalizeSemester($setting?->semester);

        if (! $setting?->academic_year || $semester === null || ! $setting?->exam_period) {
            abort(422, 'Academic timeline is not configured.');
        }

        ExamPermit::query()
            ->where('student_profile_id', $studentProfile->id)
            ->where('academic_year', $setting->academic_year)
            ->where('semester', $semester)
            ->where('exam_period', $setting->exam_period)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);
    }

    public function qrPayload(ExamPermit $permit): string
    {
        return json_encode([
            'token' => $permit->qr_token,
            'student_profile_id' => $permit->student_profile_id,
            'academic_year' => $permit->academic_year,
            'semester' => (int) $permit->semester,
            'exam_period' => $permit->exam_period,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
