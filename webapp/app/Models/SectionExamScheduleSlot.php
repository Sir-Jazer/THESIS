<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionExamScheduleSlot extends Model
{
    protected $fillable = [
        'section_exam_schedule_id',
        'exam_matrix_slot_id',
        'slot_date',
        'start_time',
        'end_time',
        'is_fixed',
        'subject_id',
        'room_id',
        'is_manual_assignment',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'is_fixed' => 'boolean',
            'is_manual_assignment' => 'boolean',
        ];
    }

    public function schedule()
    {
        return $this->belongsTo(SectionExamSchedule::class, 'section_exam_schedule_id');
    }

    public function matrixSlot()
    {
        return $this->belongsTo(ExamMatrixSlot::class, 'exam_matrix_slot_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function proctors()
    {
        return $this->belongsToMany(User::class, 'section_exam_schedule_slot_proctors', 'section_exam_schedule_slot_id', 'proctor_id')->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(ExamAttendance::class, 'section_exam_schedule_slot_id');
    }
}
