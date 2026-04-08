<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['program_id', 'year_level', 'section_code', 'proctor_id'];

    public function program() { return $this->belongsTo(Program::class); }
    public function proctor() { return $this->belongsTo(User::class, 'proctor_id'); }
    public function students() { return $this->hasMany(StudentProfile::class); }

    public function getDisplayNameAttribute(): string
    {
        return $this->program->code . ' ' . $this->year_level . $this->section_code;
    }
}
