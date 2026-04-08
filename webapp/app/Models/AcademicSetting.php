<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSetting extends Model
{
    protected $fillable = ['academic_year', 'semester', 'exam_period'];

    public static function current(): ?self
    {
        return static::latest()->first();
    }
}
