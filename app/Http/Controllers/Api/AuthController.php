<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * LOGIN USER (Admin / Karyawan)
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Optional: single session -> $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message'    => 'Logged in successfully.',
            'email'      => $user->email,
            'token'      => $token,
            'token_type' => 'Bearer',

            // 🔹 Tambahan: kirim user agar FE bisa simpan role, nik, dll
            'user'       => [
                'id'    => $user->id,
                'nik'   => $user->nik,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * GET USER INFO + PROFILE
     */
    public function me(Request $request)
    {
        $u = $request->user()->load('profile');
        $p = $u->profile;

        return response()->json([
            'id'    => $u->id,
            'nik'   => $u->nik,
            'name'  => $u->name,
            'email' => $u->email,
            'role'  => $u->role,

            'profile' => [
                'nama_lengkap'        => $p->nama_lengkap ?? null,
                'gelar_akademik'      => $p->gelar_akademik ?? null,
                'nik'                 => $p->nik ?? null,
                'pendidikan'          => $p->pendidikan ?? null,
                'no_ktp'              => $p->no_ktp ?? null,
                'tempat_lahir'        => $p->tempat_lahir ?? null,
                'tanggal_lahir'       => $p?->tanggal_lahir?->toDateString(),
                'tanggal_lahir_ddmmyy'=> $p?->tanggal_lahir?->format('d/m/Y'),
                'jenis_kelamin'       => $p->jenis_kelamin ?? null,
                'agama'               => $p->agama ?? null,
                'jabatan_terakhir'    => $p->jabatan_terakhir ?? null,
                'alamat_rumah'        => $p->alamat_rumah ?? null,
                'handphone'           => $p->handphone ?? null,
                'email_pribadi'       => $p->email_pribadi ?? null,
                'npwp'                => $p->npwp ?? null,
                'suku'                => $p->suku ?? null,
                'golongan_darah'      => $p->golongan_darah ?? null,
                'status_perkawinan'   => $p->status_perkawinan ?? null,
                'penilaian_kerja'     => $p->penilaian_kerja ?? null,
                'pencapaian'          => $p->pencapaian ?? null,
            ]
        ]);
    }

    /**
     * LOGOUT (hapus token saat ini)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * CHANGE PASSWORD
     */
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'new_password'     => ['required', 'string', 'min:6', 'confirmed'],
            // FE must send: new_password_confirmation
        ]);

        $user = $request->user();

        // Check old password
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password lama salah.'
            ], 422);
        }

        // Update password (Laravel auto-hash)
        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diperbarui.'
        ]);
    }
}
