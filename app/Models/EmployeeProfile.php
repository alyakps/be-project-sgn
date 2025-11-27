<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $table = 'employee_profiles';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'gelar_akademik',
        'nik',
        'pendidikan',
        'no_ktp',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'jabatan_terakhir',
        'alamat_rumah',
        'handphone',
        'email_pribadi',
        'npwp',
        'suku',
        'golongan_darah',
        'status_perkawinan',
        'penilaian_kerja',
        'pencapaian',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
