<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $table = 'assessments';

    protected $fillable = [
        'user_id',
        'unit_kerja',
        'tahun_penilaian',
        'hard_score',
        'soft_score',
    ];
}
