<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    /**
     * Register a new user - Step 1: Store data and send OTP
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'avatar' => 'nullable|string|max:500',
        ]);

        $email = $validated['email'];

        // Hash password before storing
        $registrationData = [
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'avatar' => $validated['avatar'] ?? null,
        ];

        // Store registration data in Redis (5 minutes TTL)
        $this->otpService->storeRegistrationData($email, $registrationData);

        // Generate and send OTP
        $otpResult = $this->otpService->generateAndSend($email);

        if (!$otpResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $otpResult['message'],
                'retry_after' => $otpResult['retry_after'] ?? null
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration initiated. Please check your email for OTP verification.',
            'email' => $email,
            'otp_sent' => true,
            'expires_in' => $otpResult['expires_in'] * 60 // Convert to seconds
        ], 201);
    }

    /**
     * Register a new user - Step 2: Verify OTP and complete registration
     */
    public function verifyAndCompleteRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = $validated['email'];
        $otp = $validated['otp'];

        // Verify OTP
        if (!$this->otpService->verify($email, $otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code'
            ], 400);
        }

        // Get registration data from Redis
        $registrationData = $this->otpService->getRegistrationData($email);

        if (!$registrationData) {
            return response()->json([
                'success' => false,
                'message' => 'Registration data expired. Please register again.'
            ], 400);
        }

        // Create user in database
        $user = User::create($registrationData);

        // Assign default subscriber role
        $subscriberRole = Role::where('slug', 'subscriber')->first();
        if ($subscriberRole) {
            $user->roles()->attach($subscriberRole->id);
        }

        // Clear registration data from Redis
        $this->otpService->clearRegistrationData($email);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully. You can now login with your credentials.',
            'email' => $email
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'use_cookies' => 'nullable|boolean',
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];
        $useCookies = $validated['use_cookies'] ?? false;

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not create token'
            ], 500);
        }

        $user = auth()->user()->load('roles');
        $expiresIn = JWTAuth::factory()->getTTL() * 60;

        $response = response()->json([
            'message' => 'Login successful',
            'user' => $user,
            // 'token' => $token, // Do not return token in body
            'token_type' => 'bearer',
            'expires_in' => $expiresIn
        ]);

        // Always set httpOnly cookie with SameSite=none for cross-domain
        $response->cookie(
            'jwt_token',
            $token,
            $expiresIn / 60, // minutes
            '/',
            null,
            true, // secure (required for SameSite=none)
            true, // httpOnly
            false,
            'none' // SameSite: none for cross-domain cookies
        );

        return $response;
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            
            $response = response()->json([
                'message' => 'Logged out successfully'
            ]);

            // Always clear cookie with SameSite=none for cross-domain
            $response->cookie(
                'jwt_token',
                '',
                -1, // expire immediately
                '/',
                null,
                true, // secure (required for SameSite=none)
                true,
                false,
                'none' // SameSite: none for cross-domain cookies
            );

            return $response;
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth()->user()->load(['roles.permissions', 'subscriptions.plan']);

            return response()->json([
                'user' => $user,
                'can_access_premium' => $user->canAccessPremium(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            $expiresIn = JWTAuth::factory()->getTTL() * 60;

            $response = response()->json([
                'message' => 'Token refreshed successfully',
                // 'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn
            ]);

            // Set new httpOnly cookie with SameSite=none for cross-domain
            $response->cookie(
                'jwt_token',
                $token,
                $expiresIn / 60, // minutes
                '/',
                null,
                true, // secure (required for SameSite=none)
                true, // httpOnly
                false,
                'none' // SameSite: none for cross-domain cookies
            );

            return $response;
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not refresh token'
            ], 500);
        }
    }

    /**
     * Revoke all tokens (invalidate current token)
     */
    public function revokeAll(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Token revoked successfully'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not revoke token'
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password'])
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Update profile (name, avatar only - email cannot be changed)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'avatar' => 'nullable|image|max:5120', // 5MB max for file uploads
        ]);

        $user = auth()->user();

        // Handle avatar file upload
        if ($request->hasFile('avatar')) {
            $imageService = new \App\Services\ImageService();
            $validated['avatar'] = $imageService->uploadFromFile(
                $request->file('avatar'),
                'avatars'
            );
        }

        $user->update($validated);
        $user->load('roles');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}

