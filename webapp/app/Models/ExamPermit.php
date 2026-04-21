<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamPermit extends Model
{
    protected $fillable = [
        'student_profile_id',
        'academic_year',
        'semester',
        'exam_period',
        'qr_token',
        'generated_by',
        'generated_at',
        'revoked_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'revoked_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function attendances()
    {
        return $this->hasMany(ExamAttendance::class);
    }
}
