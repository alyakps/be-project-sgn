<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HardCompetency extends Model
{
    use HasFactory;

<<<<<<< HEAD
    // Nama tabel sesuai migration
    protected $table = 'hard_competencies';

    // Kolom yang boleh diisi (mass assignable)
=======
    protected $table = 'hard_competencies';

>>>>>>> 8be18af (update api hard competency)
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

<<<<<<< HEAD
    // Casting tipe data
=======
>>>>>>> 8be18af (update api hard competency)
    protected $casts = [
        'nilai' => 'integer',
    ];

<<<<<<< HEAD
    /**
     * Scope untuk filter berdasarkan NIK.
     */
=======
>>>>>>> 8be18af (update api hard competency)
    public function scopeForNik(Builder $query, string $nik): Builder
    {
        return $query->where('nik', $nik);
    }

<<<<<<< HEAD
    /**
     * Scope untuk pencarian bebas.
     */
=======
>>>>>>> 8be18af (update api hard competency)
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
<<<<<<< HEAD
=======

>>>>>>> 8be18af (update api hard competency)
        return $query->where(function ($q) use ($term) {
            $q->where('kode', 'like', $term)
              ->orWhere('nama_kompetensi', 'like', $term)
              ->orWhere('job_family_kompetensi', 'like', $term)
              ->orWhere('sub_job_family_kompetensi', 'like', $term)
              ->orWhere('deskripsi', 'like', $term);
        });
    }
}
