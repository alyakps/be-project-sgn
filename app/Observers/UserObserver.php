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

        /**
         * ✅ Kunci: saat create, unit_kerja juga dianggap "changed",
         * tapi pada beberapa flow bisa saja nilainya null.
         * Kita tetap sync setiap save, selama key unit_kerja memang ada.
         */
        $unitKerjaBaru = $user->unit_kerja;

        // Pastikan profile ada, lalu sinkron unit_kerja-nya
        EmployeeProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                // sekalian jaga konsistensi beberapa field dasar
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,

                // single source of truth tetap users.unit_kerja
                'unit_kerja'    => $unitKerjaBaru,
            ]
        );
    }
}
