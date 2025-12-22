<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap'      => ['nullable', 'string', 'max:150'],
            'gelar_akademik'    => ['nullable', 'string', 'max:100'],
            'nik'               => ['nullable', 'string', 'max:50'],
            'pendidikan'        => ['nullable', 'string', 'max:100'],

            // ✅ mandatory (sesuai kebutuhan UI kamu)
            'no_ktp'            => ['required', 'string', 'max:50'],
            'tanggal_lahir'     => ['required', 'date'],
            'alamat_rumah'      => ['required', 'string'],
            'handphone'         => ['required', 'string', 'max:50'],
            'status_perkawinan' => ['required', 'string', 'max:50'],

            'tempat_lahir'      => ['nullable', 'string', 'max:100'],
            'jenis_kelamin'     => ['nullable', 'string', 'max:20'],
            'agama'             => ['nullable', 'string', 'max:50'],
            'jabatan_terakhir'  => ['nullable', 'string', 'max:150'],

            // ✅ unit_kerja sengaja DIHILANGKAN dari rule (karyawan tidak boleh update)

            'email_pribadi'     => ['nullable', 'email', 'max:150'],
            'npwp'              => ['nullable', 'string', 'max:50'],
            'suku'              => ['nullable', 'string', 'max:50'],
            'golongan_darah'    => ['nullable', 'string', 'max:5'],
            'penilaian_kerja'   => ['nullable', 'string'],
            'pencapaian'        => ['nullable', 'string'],

            'photo'             => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_ktp.required'            => 'No. KTP wajib diisi.',
            'tanggal_lahir.required'     => 'Tanggal lahir wajib diisi.',
            'alamat_rumah.required'      => 'Alamat rumah wajib diisi.',
            'handphone.required'         => 'Handphone wajib diisi.',
            'status_perkawinan.required' => 'Status perkawinan wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Biar string "   " dianggap kosong (kena required)
        $input = $this->all();
        foreach ($input as $k => $v) {
            if (is_string($v)) {
                $input[$k] = trim($v);
            }
        }
        $this->replace($input);
    }
}
