<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamMatrixSubjectBatchSectionAssignment extends Model
{
    protected $fillable = [
        'exam_matrix_id',
        'subject_id',
        'program_id',
        'year_level',
        'section_id',
        'batch_no',
    ];

    public function matrix()
    {
        return $this->belongsTo(ExamMatrix::class, 'exam_matrix_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
