<?php

namespace App\Models;

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

    public function isUploaded(): bool
    {
        return $this->status === 'uploaded';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
