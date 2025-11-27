<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeProfileController extends Controller
{
    /**
     * (KARYAWAN) GET /api/karyawan/profile
     * Karyawan lihat profil diri sendiri.
     */
    public function showSelf(Request $request)
    {
        $user = $request->user()->load('profile');

        // kalau belum punya profile → buat empty
        $profile = $user->profile ?? EmployeeProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
            ]
        );

        return response()->json([
            'data' => [
                'user' => [
                    'id'    => $user->id,
                    'nik'   => $user->nik,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
                'profile' => [
                    'id'                => $profile->id,
                    'nama_lengkap'      => $profile->nama_lengkap,
                    'gelar_akademik'    => $profile->gelar_akademik,
                    'nik'               => $profile->nik,
                    'pendidikan'        => $profile->pendidikan,
                    'no_ktp'            => $profile->no_ktp,
                    'tempat_lahir'      => $profile->tempat_lahir,
                    'tanggal_lahir'     => optional($profile->tanggal_lahir)->toDateString(),
                    'jenis_kelamin'     => $profile->jenis_kelamin,
                    'agama'             => $profile->agama,
                    'jabatan_terakhir'  => $profile->jabatan_terakhir,
                    'alamat_rumah'      => $profile->alamat_rumah,
                    'handphone'         => $profile->handphone,
                    'email_pribadi'     => $profile->email_pribadi,
                    'npwp'              => $profile->npwp,
                    'suku'              => $profile->suku,
                    'golongan_darah'    => $profile->golongan_darah,
                    'status_perkawinan' => $profile->status_perkawinan,
                    'penilaian_kerja'   => $profile->penilaian_kerja,
                    'pencapaian'        => $profile->pencapaian,
                ],
            ],
        ]);
    }

    /**
     * (KARYAWAN) PUT /api/karyawan/profile
     * Karyawan update profil dirinya sendiri.
     */
    public function updateSelf(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nama_lengkap'      => ['nullable', 'string', 'max:150'],
            'gelar_akademik'    => ['nullable', 'string', 'max:100'],
            // nik boleh diisi tapi kalau mau dikunci, bisa di-comment
            'nik'               => ['nullable', 'string', 'max:50'],
            'pendidikan'        => ['nullable', 'string', 'max:100'],
            'no_ktp'            => ['nullable', 'string', 'max:50'],
            'tempat_lahir'      => ['nullable', 'string', 'max:100'],
            'tanggal_lahir'     => ['nullable', 'date'],
            'jenis_kelamin'     => ['nullable', 'string', 'max:20'],
            'agama'             => ['nullable', 'string', 'max:50'],
            'jabatan_terakhir'  => ['nullable', 'string', 'max:150'],
            'alamat_rumah'      => ['nullable', 'string'],
            'handphone'         => ['nullable', 'string', 'max:50'],
            'email_pribadi'     => ['nullable', 'email', 'max:150'],
            'npwp'              => ['nullable', 'string', 'max:50'],
            'suku'              => ['nullable', 'string', 'max:50'],
            'golongan_darah'    => ['nullable', 'string', 'max:5'],
            'status_perkawinan' => ['nullable', 'string', 'max:50'],
            'penilaian_kerja'   => ['nullable', 'string'],
            'pencapaian'        => ['nullable', 'string'],
        ]);

        $profile = EmployeeProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
            ]
        );

        // isi hanya field yg boleh diubah karyawan
        $profile->fill($data);
        $profile->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $profile->fresh(),
        ]);
    }

    /**
     * (ADMIN) GET /api/admin/employee-profiles
     * List profil semua karyawan (search + pagination).
     *
     * Query:
     *  - q      : cari nama / email / nik
     *  - nik    : filter nik spesifik
     *  - per_page : default 10
     */
    public function adminIndex(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('q', ''));
        $nik     = trim((string) $request->get('nik', ''));

        $query = EmployeeProfile::query()
            ->with('user') // biar bisa tampilkan email / role
            ->when($nik !== '', fn ($q) => $q->where('nik', 'like', "%{$nik}%"))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%")
                      ->orWhere('email_pribadi', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_lengkap');

        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(function (EmployeeProfile $profile) {
            return [
                'id'                => $profile->id,
                'user_id'           => $profile->user_id,
                'nik'               => $profile->nik,
                'nama_lengkap'      => $profile->nama_lengkap,
                'jabatan_terakhir'  => $profile->jabatan_terakhir,
                'pendidikan'        => $profile->pendidikan,
                'handphone'         => $profile->handphone,
                'email_pribadi'     => $profile->email_pribadi,
                'user' => [
                    'id'    => $profile->user?->id,
                    'name'  => $profile->user?->name,
                    'email' => $profile->user?->email,
                    'role'  => $profile->user?->role,
                ],
            ];
        })->values();

        return response()->json([
            'data'  => $items,
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
                'last'  => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }

    /**
     * (ADMIN) GET /api/admin/karyawan/{user}/profile
     * Admin lihat profil satu karyawan berdasarkan user_id
     */
    public function adminShowByUser(User $user)
    {
        $user->load('profile');

        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profil karyawan belum tersedia.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id'    => $user->id,
                    'nik'   => $user->nik,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
                'profile' => $profile,
            ],
        ]);
    }
}
