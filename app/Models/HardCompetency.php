<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HardCompetency extends Model
{
    use HasFactory;

    protected $table = 'hard_competencies';

    protected $fillable = [
        'nik',
        'tahun',
        'id_kompetensi',
        'kode',
        'nama_kompetensi',
        'job_family_kompetensi',
        'sub_job_family_kompetensi',
        'status',
        'nilai',
        'deskripsi',

        // ✅ WAJIB biar cancel bisa bekerja
        'is_active',
        'import_log_id',
    ];

    protected $casts = [
        'nilai' => 'integer',
        'tahun' => 'integer',
        'is_active' => 'boolean',
        'import_log_id' => 'integer',
    ];

    public function scopeForNik(Builder $query, string $nik): Builder
    {
        return $query->where('nik', $nik);
    }

    public function scopeForYear(Builder $query, ?int $tahun): Builder
    {
        if (!$tahun) return $query;
        return $query->where('tahun', $tahun);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) return $query;

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
