<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamMatrixSubjectBatch extends Model
{
    protected $fillable = [
        'exam_matrix_id',
        'subject_id',
        'batch_no',
        'exam_matrix_slot_id',
    ];

    public function matrix()
    {
        return $this->belongsTo(ExamMatrix::class, 'exam_matrix_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function matrixSlot()
    {
        return $this->belongsTo(ExamMatrixSlot::class, 'exam_matrix_slot_id');
    }
}
