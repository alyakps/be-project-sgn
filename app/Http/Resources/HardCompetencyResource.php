<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HardCompetencyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'nik'                    => $this->nik,
            'id_kompetensi'         => $this->id_kompetensi,
            'kode'                  => $this->kode,
            'nama_kompetensi'       => $this->nama_kompetensi,
            'job_family_kompetensi' => $this->job_family_kompetensi,
            'sub_job_family_kompetensi' => $this->sub_job_family_kompetensi,
            'status'                => $this->status,
            'nilai'                 => $this->nilai,
            'deskripsi'             => $this->deskripsi,
        ];
    }
}
