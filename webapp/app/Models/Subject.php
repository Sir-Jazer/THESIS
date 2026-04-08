<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['code', 'name', 'units'];

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_subjects')
            ->withPivot('year_level', 'semester');
    }

    public function prerequisites()
    {
        return $this->belongsToMany(Subject::class, 'subject_prerequisites', 'subject_id', 'prerequisite_id');
    }

    public function corequisites()
    {
        return $this->belongsToMany(Subject::class, 'subject_corequisites', 'subject_id', 'corequisite_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'student_subjects')->withTimestamps();
    }
}
