<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProctorProfile extends Model
{
    protected $fillable = ['user_id', 'employee_id', 'department'];

    public function user() { return $this->belongsTo(User::class); }
    public function sections() { return $this->hasMany(Section::class, 'proctor_id', 'user_id'); }
}
