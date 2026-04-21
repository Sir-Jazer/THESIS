<?php

namespace App\Services\Portal;

use App\Models\AcademicSetting;
use App\Models\ExamPermit;
use App\Models\SectionExamScheduleSlot;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamPortalService
{
    public const PERIODS = ['Prelim', 'Midterm', 'Prefinals', 'Finals'];

    public function currentSetting(): ?AcademicSetting
    {
        return AcademicSetting::current();
    }

    public function normalizeSemester(string|int|null $semester): ?int
    {
        return match ($semester) {
            1, '1', '1st Semester' => 1,
            2, '2', '2nd Semester' => 2,
            default => null,
        };
    }

    public function resolvePeriod(?string $requestedPeriod, ?AcademicSetting $setting): string
    {
        if ($requestedPeriod !== null && in_array($requestedPeriod, self::PERIODS, true)) {
            return $requestedPeriod;
        }

        return in_array($setting?->exam_period, self::PERIODS, true)
            ? $setting->exam_period
            : self::PERIODS[0];
    }

    public function enrolledSubjectIdsForStudent(User $user, ?AcademicSetting $setting = null): Collection
    {
        $subjectIds = $user->subjects()
            ->pluck('subjects.id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($subjectIds->isNotEmpty()) {
            return $subjectIds;
        }

        $profile = $user->studentProfile;
        $semester = $this->normalizeSemester($setting?->semester);

        if (! $profile || ! $profile->program_id || ! $profile->year_level || $semester === null) {
            return collect();
        }

        return DB::table('program_subjects')
            ->where('program_id', (int) $profile->program_id)
            ->where('year_level', (int) $profile->year_level)
            ->where('semester', $semester)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function studentScheduleRows(User $user, string $period, ?AcademicSetting $setting = null): Collection
    {
        $setting ??= $this->currentSetting();
        $profile = $user->studentProfile;
        $semester = $this->normalizeSemester($setting?->semester);

        if (! $profile?->section_id || ! $setting?->academic_year || $semester === null) {
            return collect();
        }

        $subjectIds = $this->enrolledSubjectIdsForStudent($user, $setting);

        $rows = SectionExamScheduleSlot::query()
            ->with([
                'subject:id,code,course_serial_number,name',
                'room:id,name',
                'schedule.section:id,section_code,program_id,year_level',
                'proctors:id,first_name,last_name',
                'attendances' => fn ($query) => $query
                    ->where('student_profile_id', $profile->id)
                    ->with('logger:id,first_name,last_name'),
            ])
            ->whereHas('schedule', function (Builder $query) use ($profile, $setting, $semester, $period): void {
                $query->where('status', 'published')
                    ->where('section_id', $profile->section_id)
                    ->where('academic_year', $setting->academic_year)
                    ->where('semester', $semester)
                    ->where('exam_period', $period);
            })
            ->when($subjectIds->isNotEmpty(), function (Builder $query) use ($subjectIds): void {
                $query->whereIn('subject_id', $subjectIds->all());
            })
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        return $rows->map(function (SectionExamScheduleSlot $slot) {
            $attendance = $slot->attendances->first();

            return [
                'slot' => $slot,
                'subject_name' => $slot->subject?->name ?? 'Unassigned Subject',
                'subject_code' => $slot->subject?->code,
                'date' => $slot->slot_date,
                'time_label' => substr((string) $slot->start_time, 0, 5) . '-' . substr((string) $slot->end_time, 0, 5),
                'room_name' => $slot->room?->name ?? 'TBA',
                'proctor_name' => $slot->proctors->pluck('full_name')->filter()->join(', ') ?: 'TBA',
                'status' => $attendance ? 'Cleared' : 'Pending',
                'attendance' => $attendance,
            ];
        });
    }

    public function activePermitForStudent(StudentProfile $profile, ?AcademicSetting $setting = null): ?ExamPermit
    {
        $setting ??= $this->currentSetting();
        $semester = $this->normalizeSemester($setting?->semester);

        if (! $setting?->academic_year || $semester === null) {
            return null;
        }

        return ExamPermit::query()
            ->where('student_profile_id', $profile->id)
            ->where('academic_year', $setting->academic_year)
            ->where('semester', $semester)
            ->where('exam_period', $setting->exam_period)
            ->where('is_active', true)
            ->first();
    }

    public function cashierStudentRows(array $filters = [], ?AcademicSetting $setting = null)
    {
        $setting ??= $this->currentSetting();
        $semester = $this->normalizeSemester($setting?->semester);

        $query = User::query()
            ->where('role', 'student')
            ->with([
                'studentProfile.program:id,code,name',
                'studentProfile.section:id,section_code',
                'subjects:id,code,name',
            ])
            ->whereHas('studentProfile');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('studentProfile', function (Builder $profileQuery) use ($search): void {
                        $profileQuery->where('student_id', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['program_id'])) {
            $query->whereHas('studentProfile', function (Builder $profileQuery) use ($filters): void {
                $profileQuery->where('program_id', (int) $filters['program_id']);
            });
        }

        if (! empty($filters['year_level'])) {
            $query->whereHas('studentProfile', function (Builder $profileQuery) use ($filters): void {
                $profileQuery->where('year_level', (int) $filters['year_level']);
            });
        }

        $students = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(12)
            ->withQueryString();

        $students->getCollection()->transform(function (User $student) use ($setting, $semester) {
            $profile = $student->studentProfile;
            $permit = null;

            if ($profile && $setting?->academic_year && $semester !== null) {
                $permit = ExamPermit::query()
                    ->where('student_profile_id', $profile->id)
                    ->where('academic_year', $setting->academic_year)
                    ->where('semester', $semester)
                    ->where('exam_period', $setting->exam_period)
                    ->first();
            }

            $student->current_exam_permit = $permit;
            $student->enrolled_subject_count = $this->enrolledSubjectIdsForStudent($student, $setting)->count();

            return $student;
        });

        return $students;
    }
}
