<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeProfile;
use App\Models\User;
use Carbon\Carbon;

class EmployeeProfileSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'karyawan')->get();

        foreach ($users as $u) {

            // ==============================
            // BUDI
            // ==============================
            if ($u->nik === '5025211174') {
                EmployeeProfile::updateOrCreate(
                    ['user_id' => $u->id],
                    [
                        'nama_lengkap'      => 'Budi Santoso',
                        'gelar_akademik'    => 'S.T.',
                        'nik'               => '5025211174',
                        'pendidikan'        => 'S1',
                        'no_ktp'            => '35206372040463',
                        'tempat_lahir'      => 'Surabaya',

                        // ⬇️ Convert DD/MM/YYYY → YYYY-MM-DD
                        'tanggal_lahir'     => Carbon::createFromFormat('d/m/Y', '27/01/1999')->format('Y-m-d'),

                        'jenis_kelamin'     => 'Laki-laki',
                        'agama'             => 'Islam',
                        'jabatan_terakhir'  => 'Staff IT',
                        'alamat_rumah'      => 'Jl. Mawar No. 155, Surabaya',
                        'handphone'         => '081295655950',
                        'email_pribadi'     => 'budi@example.com',
                        'npwp'              => '63.411.702.1-409',
                        'suku'              => 'Jawa',
                        'golongan_darah'    => 'O',
                        'status_perkawinan' => 'Belum Menikah',
                        'penilaian_kerja'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                        'pencapaian'        => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    ]
                );
                continue;
            }

            // ==============================
            // SITI
            // ==============================
            if ($u->nik === '5025211175') {
                EmployeeProfile::updateOrCreate(
                    ['user_id' => $u->id],
                    [
                        'nama_lengkap'      => 'Siti Rahmawati',
                        'gelar_akademik'    => 'S.M.',
                        'nik'               => '5025211175',
                        'pendidikan'        => 'S1 Manajemen',
                        'no_ktp'            => '3520678901234567',
                        'tempat_lahir'      => 'Gresik',

                        // ⬇️ Convert DD/MM/YYYY
                        'tanggal_lahir'     => Carbon::createFromFormat('d/m/Y', '28/01/2000')->format('Y-m-d'),

                        'jenis_kelamin'     => 'Perempuan',
                        'agama'             => 'Islam',
                        'jabatan_terakhir'  => 'Staff Administrasi',
                        'alamat_rumah'      => 'Jl. Melati No. 24, Gresik',
                        'handphone'         => '081234567812',
                        'email_pribadi'     => 'siti@example.com',
                        'npwp'              => '12.345.678.9-012',
                        'suku'              => 'Jawa',
                        'golongan_darah'    => 'A',
                        'status_perkawinan' => 'Belum Menikah',
                        'penilaian_kerja'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                        'pencapaian'        => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    ]
                );
                continue;
            }

            // ==============================
            // DEFAULT USER LAIN
            // ==============================
            EmployeeProfile::updateOrCreate(
                ['user_id' => $u->id],
                [
                    'nama_lengkap'      => $u->name,
                    'gelar_akademik'    => 'S.T.',
                    'nik'               => $u->nik,
                    'pendidikan'        => 'S1',
                    'no_ktp'            => '3500xxxxxxx',
                    'tempat_lahir'      => 'Surabaya',
                    'tanggal_lahir'     => '2000-01-01',
                    'jenis_kelamin'     => 'Laki-laki',
                    'agama'             => 'Islam',
                    'jabatan_terakhir'  => 'Staff',
                    'alamat_rumah'      => 'Alamat default',
                    'handphone'         => '081234567890',
                    'email_pribadi'     => $u->email,
                    'npwp'              => '00.000.000.0-000',
                    'suku'              => 'Jawa',
                    'golongan_darah'    => 'O',
                    'status_perkawinan' => 'Belum Menikah',
                    'penilaian_kerja'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'pencapaian'        => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ]
            );
        }
    }
}
