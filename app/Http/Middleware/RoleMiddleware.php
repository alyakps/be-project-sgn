<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Pakai: ->middleware('role:admin') atau 'role:admin,karyawan'
     */
    public function handle(Request $request, Closure $next, string $rolesCsv)
    {
        $user = $request->user();

        // Belum login / token invalid
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // rolesCsv dari route: "admin" atau "admin,karyawan"
        $allowed = array_map(
            fn ($r) => trim(strtolower($r)),
            explode(',', $rolesCsv)
        );

        $userRole = strtolower(trim((string) $user->role));

        // Kalau user tidak punya role atau rolenya tidak masuk daftar allowed
        if ($userRole === '' || !in_array($userRole, $allowed, true)) {
            return response()->json([
                'message' => 'Forbidden',
                'detail'  => "Role '{$userRole}' tidak diizinkan untuk endpoint ini.",
            ], 403);
        }

        return $next($request);
    }
}
