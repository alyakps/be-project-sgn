<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'filename',
        'stored_path',
        'type',
        'tahun',
        'sukses',
        'gagal',
        'status',
        'canceled_at',
        'uploaded_by',
        'canceled_by',
    ];

    protected $casts = [
        'canceled_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function canceler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }
}
