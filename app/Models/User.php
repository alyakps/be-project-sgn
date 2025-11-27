<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// ⬇️ tambahkan ini
use App\Models\EmployeeProfile;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['nik','name','email','password','role'];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // string -> otomatis di-hash
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ⬇️ RELASI BARU: 1 user punya 1 profile
    public function profile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }
}
