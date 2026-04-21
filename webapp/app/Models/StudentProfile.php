<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = ['user_id', 'student_id', 'program_id', 'year_level', 'section_id'];

    public function user() { return $this->belongsTo(User::class); }
    public function program() { return $this->belongsTo(Program::class); }
    public function section() { return $this->belongsTo(Section::class); }
    public function examPermits() { return $this->hasMany(ExamPermit::class); }
    public function examAttendances() { return $this->hasMany(ExamAttendance::class); }
}
