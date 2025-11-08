<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Pakai: ->middleware('role:admin') atau 'role:admin,karyawan'
     */
    public function handle(Request $request, Closure $next, string $rolesCsv): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $allowed = array_map(fn($r) => trim(strtolower($r)), explode(',', $rolesCsv));
        $userRole = strtolower((string)$user->role);

        if (!in_array($userRole, $allowed, true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
