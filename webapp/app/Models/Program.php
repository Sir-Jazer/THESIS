<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['name', 'code'];

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'program_subjects')
            ->withPivot('year_level', 'semester');
    }

    public function sections() { return $this->hasMany(Section::class); }
    public function studentProfiles() { return $this->hasMany(StudentProfile::class); }
    public function examMatrices() { return $this->hasMany(ExamMatrix::class); }
    public function examSchedules() { return $this->hasMany(SectionExamSchedule::class); }
}
