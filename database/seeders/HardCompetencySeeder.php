<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HardCompetency;

class HardCompetencySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ============================
            // DATA KARYAWAN 1 — BUDI
            // ============================
            [
                'nik' => '5025211174',
                'id_kompetensi' => '4821',
                'kode' => 'HAK.MAK.008',
                'nama_kompetensi' => 'Verifikasi Bahan Baku',
                'job_family_kompetensi' => 'Produksi',
                'sub_job_family_kompetensi' => 'Operator Giling',
                'status' => 'tercapai',
                'nilai' => 92,
                'deskripsi' => 'Memastikan bahan baku memenuhi standar mutu sebelum proses produksi dimulai.',
            ],
            [
                'nik' => '5025211174',
                'id_kompetensi' => '1942',
                'kode' => 'HAK.MAK.009',
                'nama_kompetensi' => 'Pengawasan Proses Giling',
                'job_family_kompetensi' => 'Produksi',
                'sub_job_family_kompetensi' => 'Operator Giling',
                'status' => 'tidak tercapai',
                'nilai' => 65,
                'deskripsi' => 'Melakukan pengawasan jalannya proses penggilingan untuk menjaga kualitas hasil.',
            ],

            // ============================
            // DATA KARYAWAN 2 — SITI
            // ============================
            [
                'nik' => '5025211175',
                'id_kompetensi' => '5001',
                'kode' => 'HAK.MNT.001',
                'nama_kompetensi' => 'Pemeriksaan Mesin Harian',
                'job_family_kompetensi' => 'Maintenance',
                'sub_job_family_kompetensi' => 'Teknisi Mesin',
                'status' => 'tercapai',
                'nilai' => 88,
                'deskripsi' => 'Memastikan kondisi mesin dalam keadaan aman dan siap digunakan.',
            ],
            [
                'nik' => '5025211175',
                'id_kompetensi' => '5002',
                'kode' => 'HAK.SFT.002',
                'nama_kompetensi' => 'Penerapan SOP Keselamatan',
                'job_family_kompetensi' => 'K3',
                'sub_job_family_kompetensi' => 'Safety Officer',
                'status' => 'tidak tercapai',
                'nilai' => 72,
                'deskripsi' => 'Menjalankan prosedur keselamatan kerja di area produksi.',
            ],
        ];

        foreach ($rows as $row) {
            HardCompetency::updateOrCreate(
                [
                    'nik'  => $row['nik'],
                    'kode' => $row['kode'], // KUNCI UNIK
                ],
                array_merge($row, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
