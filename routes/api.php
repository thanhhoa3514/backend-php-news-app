<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\OtpController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



// API Version 1
Route::prefix('v1')->group(function () {
    
    // Authentication routes (public with rate limiting)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/register/verify', [AuthController::class, 'verifyAndCompleteRegistration']);
        Route::post('/login', [AuthController::class, 'login']);
        
        // Social Authentication
        Route::get('/{provider}', [SocialAuthController::class, 'redirectToProvider'])
            ->where('provider', 'google|facebook');
        Route::get('/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
            ->where('provider', 'google|facebook');
        
        // Protected auth routes
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/revoke-all', [AuthController::class, 'revokeAll']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
        });
    });

    // OTP routes (public with rate limiting)
    Route::prefix('otp')->middleware('throttle:60,1')->group(function () {
        Route::post('/send', [OtpController::class, 'send']);
        Route::post('/verify', [OtpController::class, 'verify']);
        Route::post('/status', [OtpController::class, 'status']);
    });

    // News routes (public read, protected write)
    Route::prefix('news')->group(function () {
        Route::get('/', [NewsController::class, 'index']);
        Route::get('/search', [NewsController::class, 'search']);
        Route::get('/{id}', [NewsController::class, 'show']);
        
        Route::middleware('auth:api')->group(function () {
            Route::get('/all', [NewsController::class, 'all']);
            Route::post('/', [NewsController::class, 'store']);
            Route::put('/{id}', [NewsController::class, 'update']);
            Route::patch('/{id}', [NewsController::class, 'update']);
            Route::delete('/{id}', [NewsController::class, 'destroy']);
        });
    });

    // Category routes (public read, protected write)
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
        Route::get('/{slug}/news', [CategoryController::class, 'news']);
        
        Route::middleware('auth:api')->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::patch('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });
    });

    // Tag routes (public read, protected write)
    Route::prefix('tags')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::get('/{slug}', [TagController::class, 'show']);
        Route::get('/{slug}/news', [TagController::class, 'news']);
        
        Route::middleware('auth:api')->group(function () {
            Route::post('/', [TagController::class, 'store']);
            Route::put('/{id}', [TagController::class, 'update']);
            Route::patch('/{id}', [TagController::class, 'update']);
            Route::delete('/{id}', [TagController::class, 'destroy']);
        });
    });

    // Plan routes (public read, protected write)
    Route::prefix('plans')->group(function () {
        Route::get('/', [PlanController::class, 'index']);
        Route::get('/{id}', [PlanController::class, 'show']);
        
        Route::middleware('auth:api')->group(function () {
            Route::post('/', [PlanController::class, 'store']);
            Route::put('/{id}', [PlanController::class, 'update']);
            Route::patch('/{id}', [PlanController::class, 'update']);
            Route::delete('/{id}', [PlanController::class, 'destroy']);
        });
    });

    // Subscription routes (protected)
    Route::middleware('auth:api')->prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::get('/{id}', [SubscriptionController::class, 'show']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::put('/{id}', [SubscriptionController::class, 'update']);
        Route::patch('/{id}', [SubscriptionController::class, 'update']);
        Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('/{id}/activate', [SubscriptionController::class, 'activate']);
        Route::delete('/{id}', [SubscriptionController::class, 'destroy']);
    });

    // User routes (protected)
    Route::middleware('auth:api')->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::get('/{id}/roles', [UserController::class, 'roles']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::patch('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Role routes (protected)
    Route::middleware('auth:api')->prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::get('/{id}/users', [RoleController::class, 'users']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::patch('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    // Permission routes (protected)
    Route::middleware('auth:api')->prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('/{id}', [PermissionController::class, 'show']);
        Route::get('/role/{roleId}', [PermissionController::class, 'byRole']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::patch('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
    });
});

