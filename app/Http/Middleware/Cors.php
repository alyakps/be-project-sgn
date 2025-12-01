<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
        ];

        $origin = $request->headers->get('Origin');

        if (in_array($origin, $allowedOrigins, true)) {
            $activeOrigin = $origin;
        } else {
            $activeOrigin = $allowedOrigins[0];
        }

        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $activeOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header(
                    'Access-Control-Allow-Headers',
                    'Content-Type, Authorization, X-Requested-With, Accept, Origin'
                )
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', $activeOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, X-Requested-With, Accept, Origin'
        );
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
