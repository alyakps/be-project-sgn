<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HardCompetency extends Model
{
    use HasFactory;

    // Nama tabel sesuai migration
    protected $table = 'hard_competencies';

    // Kolom yang boleh diisi (mass assignable)
    protected $fillable = [
        'nik',
        'id_kompetensi',
        'kode',
        'nama_kompetensi',
        'job_family_kompetensi',
        'sub_job_family_kompetensi',
        'status',
        'nilai',
        'deskripsi',
    ];

    // Casting tipe data
    protected $casts = [
        'nilai' => 'integer',
    ];

    /**
     * Scope untuk filter berdasarkan NIK.
     */
    public function scopeForNik(Builder $query, string $nik): Builder
    {
        return $query->where('nik', $nik);
    }

    /**
     * Scope untuk pencarian bebas.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('kode', 'like', $term)
              ->orWhere('nama_kompetensi', 'like', $term)
              ->orWhere('job_family_kompetensi', 'like', $term)
              ->orWhere('sub_job_family_kompetensi', 'like', $term)
              ->orWhere('deskripsi', 'like', $term);
        });
    }
}
