<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $nik = $this->route('nik');

        return [
            // USERS
            'name'       => ['required', 'string', 'max:255'],
            'email'      => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($nik, 'nik'),
            ],
            'unit_kerja' => ['nullable', 'string', 'max:255'],

            // PROFILE
            'jabatan_terakhir' => ['nullable', 'string', 'max:255'],
            'pendidikan'       => ['nullable', 'string', 'max:100'],
            'handphone'        => ['nullable', 'string', 'max:50'],
            'alamat_rumah'     => ['nullable', 'string'],
            'penilaian_kerja'  => ['nullable', 'string'],
            'pencapaian'       => ['nullable', 'string'],
        ];
    }
}
