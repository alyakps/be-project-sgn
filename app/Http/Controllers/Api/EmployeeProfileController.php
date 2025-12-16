<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmployeeProfileRequest;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeProfileController extends Controller
{
    /**
     * (KARYAWAN) GET /api/karyawan/profile
     * Karyawan lihat profil diri sendiri.
     */
    public function showSelf(Request $request)
    {
        $user = $request->user()->load('profile');

        // kalau belum punya profile → buat default
        $profile = $user->profile ?? EmployeeProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
            ]
        );

        // ✅ Single source of truth: users.unit_kerja
        $unit = $user->unit_kerja;

        // ✅ Auto-backfill: kalau di table employee_profiles masih null / beda, sync otomatis
        // saveQuietly supaya tidak memicu event berantai yang tidak perlu
        if ($profile->unit_kerja !== $unit) {
            $profile->unit_kerja = $unit;
            $profile->saveQuietly();
        }

        return response()->json([
            'data' => [
                'user'    => $this->transformUser($user),
                'profile' => $this->transformProfile($profile),
            ],
        ]);
    }

    /**
     * (KARYAWAN) POST /api/karyawan/profile
     * Karyawan update profil dirinya sendiri.
     */
    public function updateSelf(UpdateEmployeeProfileRequest $request)
    {
        $user = $request->user();

        // pastikan profile ada dulu
        $profile = EmployeeProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
            ]
        );

        // ==== HANDLE FOTO JIKA DIUPLOAD ====
        if ($request->hasFile('photo')) {
            // hapus foto lama kalau ada
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }

            // simpan foto baru
            $path = $request->file('photo')->store('employee_photos', 'public');
            $profile->photo_path = $path;
        }

        // 🚫 Abaikan unit_kerja dari input user
        $data = $request->except('photo');
        unset($data['unit_kerja']);

        $profile->fill($data);
        $profile->save();

        // ✅ pastikan tersinkron juga setelah save (optional backfill)
        if ($profile->unit_kerja !== $user->unit_kerja) {
            $profile->unit_kerja = $user->unit_kerja;
            $profile->saveQuietly();
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $this->transformProfile($profile),
        ]);
    }

    /**
     * (ADMIN) GET /api/admin/employee-profiles
     * List profil semua karyawan (search + pagination).
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
            $user = $profile->user;

            return [
                'id'               => $profile->id,
                'user_id'          => $profile->user_id,
                'nik'              => $profile->nik,
                'nama_lengkap'     => $profile->nama_lengkap,
                'jabatan_terakhir' => $profile->jabatan_terakhir,
                'pendidikan'       => $profile->pendidikan,
                'handphone'        => $profile->handphone,
                'email_pribadi'    => $profile->email_pribadi,

                // ✅ tampilkan unit kerja dari users
                'unit_kerja'       => $user?->unit_kerja,

                'photo_url'        => $profile->photo_path
                    ? Storage::disk('public')->url($profile->photo_path)
                    : null,
                'user'             => $user
                    ? $this->transformUser($user)
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
     * (ADMIN) GET /api/admin/karyawan/{nik}/profile
     * Admin lihat profil satu karyawan berdasarkan NIK.
     */
    public function adminShowByNik(string $nik)
    {
        $profile = EmployeeProfile::with('user')
            ->where('nik', $nik)
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil karyawan tidak ditemukan.',
            ], 404);
        }

        // ✅ Single source of truth: users.unit_kerja
        if ($profile->user) {
            $unit = $profile->user->unit_kerja;

            // ✅ Auto-backfill juga untuk endpoint admin
            if ($profile->unit_kerja !== $unit) {
                $profile->unit_kerja = $unit;
                $profile->saveQuietly();
            }
        }

        return response()->json([
            'data' => [
                'user'    => $profile->user
                    ? $this->transformUser($profile->user)
                    : null,
                'profile' => $this->transformProfile($profile),
            ],
        ]);
    }

    // ==========================
    // Helper transform
    // ==========================
    private function transformUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'nik'        => $user->nik,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'unit_kerja' => $user->unit_kerja,
        ];
    }

    private function transformProfile(EmployeeProfile $profile): array
    {
        return [
            'id'                => $profile->id,
            'photo_url'         => $profile->photo_path
                ? Storage::disk('public')->url($profile->photo_path)
                : null,
            'nama_lengkap'      => $profile->nama_lengkap,
            'gelar_akademik'    => $profile->gelar_akademik,
            'nik'               => $profile->nik,
            'pendidikan'        => $profile->pendidikan,
            'no_ktp'            => $profile->no_ktp,
            'tempat_lahir'      => $profile->tempat_lahir,
            'tanggal_lahir'     => $profile->tanggal_lahir?->toDateString(),
            'jenis_kelamin'     => $profile->jenis_kelamin,
            'agama'             => $profile->agama,
            'jabatan_terakhir'  => $profile->jabatan_terakhir,
            'unit_kerja'        => $profile->unit_kerja,
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
