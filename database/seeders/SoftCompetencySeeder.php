<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoftCompetency;

class SoftCompetencySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'nik' => '5025211176',
                'tahun' => 2025,
                'id_kompetensi' => '964',
                'kode' => 'CIN',
                'nama_kompetensi' => 'Creativity & Innovation (Kreativitas dan Inovasi)',
                'status' => 'tidak tercapai',
                'nilai' => 60,
                'deskripsi' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            ],
            [
                'nik' => '5025211176',
                'tahun' => 2025,
                'id_kompetensi' => '965',
                'kode' => 'TRL',
                'nama_kompetensi' => 'Transformational Leadership (Kepemimpinan Transformasi)',
                'status' => 'tercapai',
                'nilai' => 100,
                'deskripsi' => 'Mampu menginspirasi orang lain untuk berubah.',
            ],
            [
                'nik' => '5025211176',
                'tahun' => 2025,
                'id_kompetensi' => '966',
                'kode' => 'NEP',
                'nama_kompetensi' => 'Nurturing and Empowering People',
                'status' => 'tercapai',
                'nilai' => 98,
                'deskripsi' => 'Membina dan memberdayakan anggota tim.',
            ],
            [
                'nik' => '5025211176',
                'tahun' => 2025,
                'id_kompetensi' => '964',
                'kode' => 'CIN',
                'nama_kompetensi' => 'Creativity & Innovation (Kreativitas dan Inovasi)',
                'status' => 'tercapai',
                'nilai' => 80,
                'deskripsi' => 'Data karyawan lain untuk contoh perhitungan average.',
            ],
        ];

        foreach ($rows as $row) {
            SoftCompetency::updateOrCreate(
                [
                    'nik'           => $row['nik'],
                    'id_kompetensi' => $row['id_kompetensi'],
                    'tahun'         => $row['tahun'],
                ],
                array_merge($row, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
