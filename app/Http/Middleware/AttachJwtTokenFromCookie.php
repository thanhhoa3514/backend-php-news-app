<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachJwtTokenFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Illuminate\Support\Facades\Log::info('AttachJwtTokenFromCookie: Checking for cookie');
        if ($request->hasCookie('jwt_token')) {
            \Illuminate\Support\Facades\Log::info('AttachJwtTokenFromCookie: Cookie found');
            if (!$request->header('Authorization')) {
                $token = $request->cookie('jwt_token');
                $request->headers->set('Authorization', 'Bearer ' . $token);
                \Illuminate\Support\Facades\Log::info('AttachJwtTokenFromCookie: Authorization header set');
            }
        } else {
            \Illuminate\Support\Facades\Log::info('AttachJwtTokenFromCookie: Cookie NOT found');
        }

        return $next($request);
    }
}
