<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Biar lebih aman, tetap cek user admin.
        // Route sudah pakai middleware role:admin, tapi ini extra guard.
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // role user (admin / karyawan)
            'role' => ['required', 'string', 'in:karyawan,admin'],

            // data identitas dasar
            'nik'  => ['required', 'string', 'max:50', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:255'],

            // unit_kerja dari dropdown master, sifatnya opsional
            'unit_kerja' => ['nullable', 'string', 'max:255'],

            // profil (disimpan ke employee_profiles)
            'jabatan_terakhir' => ['nullable', 'string', 'max:255'],

            // login details
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],

            'password' => ['required', 'string', 'min:6', 'confirmed'],
            // => FE wajib kirim field "password_confirmation"
        ];
    }

    public function attributes(): array
    {
        // supaya pesan error human friendly
        return [
            'role'              => 'role',
            'nik'               => 'NIK',
            'name'              => 'nama lengkap',
            'unit_kerja'        => 'unit kerja',
            'jabatan_terakhir'  => 'jabatan terakhir',
            'email'             => 'email',
            'password'          => 'password',
        ];
    }
}
