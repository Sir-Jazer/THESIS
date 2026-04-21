<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttendance extends Model
{
    protected $fillable = [
        'section_exam_schedule_slot_id',
        'student_profile_id',
        'exam_permit_id',
        'logged_by',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
        ];
    }

    public function slot()
    {
        return $this->belongsTo(SectionExamScheduleSlot::class, 'section_exam_schedule_slot_id');
    }

    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function permit()
    {
        return $this->belongsTo(ExamPermit::class, 'exam_permit_id');
    }

    public function logger()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
