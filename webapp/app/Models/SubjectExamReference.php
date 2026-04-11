<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectExamReference extends Model
{
    protected $fillable = [
        'subject_id',
        'academic_year',
        'semester',
        'exam_period',
        'exam_reference_number',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
