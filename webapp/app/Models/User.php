<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'status',
        'employee_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isStudent(): bool { return $this->role === 'student'; }
    public function isProctor(): bool { return $this->role === 'proctor'; }
    public function isCashier(): bool { return $this->role === 'cashier'; }
    public function isAcademicHead(): bool { return $this->role === 'academic_head'; }
    public function isActive(): bool { return $this->status === 'active'; }

    public function studentProfile() { return $this->hasOne(StudentProfile::class); }
    public function proctorProfile() { return $this->hasOne(ProctorProfile::class); }
    public function cashierProfile() { return $this->hasOne(CashierProfile::class); }
    public function academicHeadProfile() { return $this->hasOne(AcademicHeadProfile::class); }
    public function subjects() { return $this->belongsToMany(Subject::class, 'student_subjects')->withTimestamps(); }
    public function advisorySections() { return $this->hasMany(Section::class, 'proctor_id'); }
}
