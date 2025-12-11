<?php

namespace App\Observers;

use App\Models\User;
use App\Models\EmployeeProfile;

class UserObserver
{
    /**
     * Dipanggil setiap kali model User di-save (create / update).
     */
    public function saved(User $user): void
    {
        // Kalau kolom unit_kerja tidak ada di attributes, skip saja
        if (!array_key_exists('unit_kerja', $user->getAttributes())) {
            return;
        }

        // Hanya jalan kalau nilai unit_kerja memang berubah
        if (!$user->wasChanged('unit_kerja')) {
            return;
        }

        $unitKerjaBaru = $user->unit_kerja;

        // Pastikan profile ada, lalu sinkron unit_kerja-nya
        EmployeeProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                // sekalian jaga konsistensi beberapa field dasar
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
                'unit_kerja'    => $unitKerjaBaru,
            ]
        );
    }
}
