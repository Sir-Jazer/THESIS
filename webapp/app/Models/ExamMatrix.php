<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamMatrix extends Model
{
    protected $fillable = [
        'academic_year',
        'semester',
        'exam_period',
        'program_id',
        'name',
        'status',
        'uploaded_at',
        'uploaded_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function slots()
    {
        return $this->hasMany(ExamMatrixSlot::class)
            ->orderBy('sort_order')
            ->orderBy('slot_date')
            ->orderBy('start_time');
    }

    public function schedules()
    {
        return $this->hasMany(SectionExamSchedule::class);
    }

    public function subjectBatches()
    {
        return $this->hasMany(ExamMatrixSubjectBatch::class)
            ->orderBy('subject_id')
            ->orderBy('batch_no');
    }

    public function subjectBatchSectionAssignments()
    {
        return $this->hasMany(ExamMatrixSubjectBatchSectionAssignment::class)
            ->orderBy('subject_id')
            ->orderBy('section_id');
    }

    public function isUploaded(): bool
    {
        return $this->status === 'uploaded';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function scopeUploadedForScheduleContext(
        Builder $query,
        string $academicYear,
        int $semester,
        string $examPeriod,
        int $programId
    ): Builder {
        return $query
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->where('exam_period', $examPeriod)
            ->where('status', 'uploaded')
            ->where(function (Builder $scope) use ($programId): void {
                $scope->where('program_id', $programId)
                    ->orWhereNull('program_id');
            })
            ->orderByRaw('CASE WHEN program_id = ? THEN 0 WHEN program_id IS NULL THEN 1 ELSE 2 END', [$programId])
            ->latest('id');
    }
}
