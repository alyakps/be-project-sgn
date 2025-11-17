<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SoftCompetencyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'nik'            => $this->nik,
            'tahun'          => $this->tahun,
            'id_kompetensi'  => $this->id_kompetensi,
            'kode'           => $this->kode,
            'nama_kompetensi'=> $this->nama_kompetensi,
            'status'         => $this->status,
            'nilai'          => $this->nilai,
            'deskripsi'      => $this->deskripsi,
        ];
    }
}
