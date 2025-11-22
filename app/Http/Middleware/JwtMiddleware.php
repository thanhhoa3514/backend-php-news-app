<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // First, try to get token from Authorization header
            $token = JWTAuth::getToken();
            
            // If no token in header, try to get from cookie
            if (!$token && $request->hasCookie('jwt_token')) {
                $token = $request->cookie('jwt_token');
                JWTAuth::setToken($token);
            }
            
            if (!$token) {
                return response()->json([
                    'message' => 'Token not provided'
                ], 401);
            }
            
            // Authenticate user
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }
            
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token has expired',
                'error' => 'token_expired'
            ], 401);
            
        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token is invalid',
                'error' => 'token_invalid'
            ], 401);
            
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token could not be parsed',
                'error' => 'token_absent'
            ], 401);
        }

        return $next($request);
    }
}

