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
     * Register a new user - Direct registration without OTP for demo
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'avatar' => 'nullable|string|max:500',
        ]);

        // Create user directly (OTP disabled for demo)
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'avatar' => $validated['avatar'] ?? null,
            'email_verified_at' => now(), // Auto-verify for demo
        ]);

        // Assign default role
        $userRole = Role::where('name', 'User')->first();
        if ($userRole) {
            $user->roles()->attach($userRole->id);
        }

        $token = JWTAuth::fromUser($user);
        $user->load('roles');

        return $this->buildAuthenticatedResponse(
            $user,
            $token,
            'Registration successful',
            201
        );
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

        return $this->buildAuthenticatedResponse($user, $token, 'Login successful');
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->clearAuthCookie(response()->json([
                'message' => 'Logged out successfully'
            ]));
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
            $user = auth()->user()->load('roles');

            return $this->buildAuthenticatedResponse($user, $token, 'Token refreshed successfully');
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

            return $this->clearAuthCookie(response()->json([
                'message' => 'Token revoked successfully'
            ]));
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

    private function buildAuthenticatedResponse(
        User $user,
        string $token,
        string $message,
        int $status = 200
    ): JsonResponse {
        $expiresIn = JWTAuth::factory()->getTTL() * 60;
        $isSecure = app()->environment('production');
        $sameSite = $isSecure ? 'none' : 'lax';

        $response = response()->json([
            'message' => $message,
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
        ], $status);

        $response->cookie(
            'jwt_token',
            $token,
            $expiresIn / 60,
            '/',
            null,
            $isSecure,
            true,
            false,
            $sameSite
        );

        return $response;
    }

    private function clearAuthCookie(JsonResponse $response): JsonResponse
    {
        $isSecure = app()->environment('production');
        $sameSite = $isSecure ? 'none' : 'lax';

        $response->cookie(
            'jwt_token',
            '',
            -1,
            '/',
            null,
            $isSecure,
            true,
            false,
            $sameSite
        );

        return $response;
    }
}
