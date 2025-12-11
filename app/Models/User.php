<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\EmployeeProfile;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // 🔹 tambahkan unit_kerja di fillable
    protected $fillable = [
        'nik',
        'name',
        'email',
        'password',
        'role',
        'unit_kerja',
    ];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // string otomatis di-hash
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // 1 user punya 1 profile
    public function profile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }
}
