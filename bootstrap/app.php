<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
        then: function () {
            // These routes use the API middleware (CORS, JSON formatting) but DO NOT have the /api prefix
            Route::middleware('api')->group(function () {
                Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
                Route::get('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
                Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'store']);
                Route::put('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);
                Route::patch('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);
                Route::delete('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CORS configuration
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\AttachJwtTokenFromCookie::class,
        ]);
        
        // Rate limiting for API
        $middleware->throttleApi();
        
        // Register custom middleware aliases
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
            'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
