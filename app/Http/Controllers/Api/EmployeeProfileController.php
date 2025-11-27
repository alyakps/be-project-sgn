<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmployeeProfileRequest;
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
                'user'    => $this->transformUser($user),
                'profile' => $this->transformProfile($profile),
            ],
        ]);
    }

    /**
     * (KARYAWAN) PUT /api/karyawan/profile
     * Karyawan update profil dirinya sendiri.
     */
    public function updateSelf(UpdateEmployeeProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $profile = EmployeeProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
            ]
        );

        $profile->fill($data);
        $profile->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $this->transformProfile($profile->fresh()),
        ]);
    }

    /**
     * (ADMIN) GET /api/admin/employee-profiles
     * List profil semua karyawan (search + pagination).
     *
     * Query:
     *  - q        : cari nama / email / nik
     *  - nik      : filter nik spesifik
     *  - per_page : default 10
     */
    public function adminIndex(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('q', ''));
        $nik     = trim((string) $request->get('nik', ''));

        $query = EmployeeProfile::query()
            ->with('user')
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
                'id'               => $profile->id,
                'user_id'          => $profile->user_id,
                'nik'              => $profile->nik,
                'nama_lengkap'     => $profile->nama_lengkap,
                'jabatan_terakhir' => $profile->jabatan_terakhir,
                'pendidikan'       => $profile->pendidikan,
                'handphone'        => $profile->handphone,
                'email_pribadi'    => $profile->email_pribadi,
                'user'             => $profile->user
                    ? $this->transformUser($profile->user)
                    : null,
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
                'user'    => $this->transformUser($user),
                'profile' => $this->transformProfile($profile),
            ],
        ]);
    }

    // ==========================
    // 🔹 Helper transform
    // ==========================
    private function transformUser(User $user): array
    {
        return [
            'id'    => $user->id,
            'nik'   => $user->nik,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];
    }

    private function transformProfile(EmployeeProfile $profile): array
    {
        return [
            'id'                => $profile->id,
            'nama_lengkap'      => $profile->nama_lengkap,
            'gelar_akademik'    => $profile->gelar_akademik,
            'nik'               => $profile->nik,
            'pendidikan'        => $profile->pendidikan,
            'no_ktp'            => $profile->no_ktp,
            'tempat_lahir'      => $profile->tempat_lahir,
            // di model: protected $casts = ['tanggal_lahir' => 'date'];
            'tanggal_lahir'     => $profile->tanggal_lahir?->toDateString(),
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
        ];
    }
}
