<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route ini sudah dibatasi pakai auth di route / middleware,
        // jadi di sini cukup return true
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap'      => ['nullable', 'string', 'max:150'],
            'gelar_akademik'    => ['nullable', 'string', 'max:100'],
            // nik bisa diisi FE kalau memang mau di-edit, atau di-hide saja di FE
            'nik'               => ['nullable', 'string', 'max:50'],
            'pendidikan'        => ['nullable', 'string', 'max:100'],
            'no_ktp'            => ['nullable', 'string', 'max:50'],
            'tempat_lahir'      => ['nullable', 'string', 'max:100'],
            'tanggal_lahir'     => ['nullable', 'date'],
            'jenis_kelamin'     => ['nullable', 'string', 'max:20'],
            'agama'             => ['nullable', 'string', 'max:50'],
            'jabatan_terakhir'  => ['nullable', 'string', 'max:150'],
            'unit_kerja'        => ['nullable', 'string', 'max:100'],
            'alamat_rumah'      => ['nullable', 'string'],
            'handphone'         => ['nullable', 'string', 'max:50'],
            'email_pribadi'     => ['nullable', 'email', 'max:150'],
            'npwp'              => ['nullable', 'string', 'max:50'],
            'suku'              => ['nullable', 'string', 'max:50'],
            'golongan_darah'    => ['nullable', 'string', 'max:5'],
            'status_perkawinan' => ['nullable', 'string', 'max:50'],
            'penilaian_kerja'   => ['nullable', 'string'],
            'pencapaian'        => ['nullable', 'string'],
            'photo'             => ['nullable', 'image', 'max:2048'],
        ];
    }
}
