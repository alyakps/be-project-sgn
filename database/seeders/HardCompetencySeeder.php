<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HardCompetency;

class HardCompetencySeeder extends Seeder
{
    public function run(): void
    {
        HardCompetency::insert([
            [
                'nik' => '1000167',
                'id_kompetensi' => '4821',
                'kode' => 'HAK.MAK.008',
                'nama_kompetensi' => 'Verifikasi Bahan Baku',
                'job_family_kompetensi' => 'Produksi',
                'sub_job_family_kompetensi' => 'Operator Giling',
                'status' => 'tercapai',
                'nilai' => 92,
                'deskripsi' => 'Memastikan bahan baku memenuhi standar mutu sebelum proses produksi dimulai.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nik' => '1000167',
                'id_kompetensi' => '1942',
                'kode' => 'HAK.MAK.009',
                'nama_kompetensi' => 'Pengawasan Proses Giling',
                'job_family_kompetensi' => 'Produksi',
                'sub_job_family_kompetensi' => 'Operator Giling',
                'status' => 'tidak tercapai',
                'nilai' => 65,
                'deskripsi' => 'Melakukan pengawasan jalannya proses penggilingan untuk menjaga kualitas hasil.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
