<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamMatrixSlotSubject extends Model
{
    protected $fillable = [
        'exam_matrix_slot_id',
        'subject_id',
        'sort_order',
    ];

    public function slot()
    {
        return $this->belongsTo(ExamMatrixSlot::class, 'exam_matrix_slot_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
