<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionExamSchedule extends Model
{
    protected $fillable = [
        'exam_matrix_id',
        'section_id',
        'academic_year',
        'semester',
        'exam_period',
        'program_id',
        'status',
        'published_at',
        'published_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function matrix()
    {
        return $this->belongsTo(ExamMatrix::class, 'exam_matrix_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function slots()
    {
        return $this->hasMany(SectionExamScheduleSlot::class)->orderBy('slot_date')->orderBy('start_time');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
