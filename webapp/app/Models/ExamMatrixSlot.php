<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamMatrixSlot extends Model
{
    protected $fillable = [
        'exam_matrix_id',
        'slot_date',
        'start_time',
        'end_time',
        'is_fixed',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'is_fixed' => 'boolean',
        ];
    }

    public function matrix()
    {
        return $this->belongsTo(ExamMatrix::class, 'exam_matrix_id');
    }

    public function slotSubjects()
    {
        return $this->hasMany(ExamMatrixSlotSubject::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'exam_matrix_slot_subjects')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('exam_matrix_slot_subjects.sort_order')
            ->orderBy('exam_matrix_slot_subjects.id');
    }

    public function subjectBatches()
    {
        return $this->hasMany(ExamMatrixSubjectBatch::class, 'exam_matrix_slot_id')
            ->orderBy('subject_id')
            ->orderBy('batch_no');
    }
}
