<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialAuthController extends Controller
{
    /**
     * Redirect to provider (Google/Facebook)
     */
    public function redirectToProvider(string $provider)
    {
        try {
            $this->validateProvider($provider);
            
            return Socialite::driver($provider)
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid provider',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Handle callback from provider
     */
    public function handleProviderCallback(string $provider)
    {
        try {
            $this->validateProvider($provider);
            
            // Get user info from provider
            $providerUser = Socialite::driver($provider)->stateless()->user();
            
            // Find or create user
            $user = $this->findOrCreateUser($providerUser, $provider);
            
            // Create JWT token
            $token = JWTAuth::fromUser($user);
            
            // Load user relationships
            $user->load('roles');
            
            $expiresIn = JWTAuth::factory()->getTTL() * 60;
            
            // Redirect to frontend with user data only
            $frontendCallback = config('app.frontend_auth_callback', env('FRONTEND_AUTH_CALLBACK', 'http://localhost:3000/auth/callback'));
            
            $response = redirect()->to(
                $frontendCallback . '?user=' . urlencode(json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'roles' => $user->roles->pluck('slug'),
                ]))
            );
            
            // Set HttpOnly cookie with SameSite=none for cross-domain
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
            
        } catch (\Exception $e) {
            $frontendCallback = config('app.frontend_auth_callback', env('FRONTEND_AUTH_CALLBACK'));
            return redirect()->to(
                $frontendCallback . '?error=' . urlencode($e->getMessage())
            );
        }
    }

    /**
     * Find or create user from social provider
     */
    private function findOrCreateUser($providerUser, string $provider)
    {
        // Check if user already linked this provider
        $userProvider = UserProvider::where('provider_name', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($userProvider) {
            return $userProvider->user;
        }

        // Check if user exists with this email
        $user = User::where('email', $providerUser->getEmail())->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'User',
                'email' => $providerUser->getEmail(),
                'password' => Hash::make(Str::random(24)),
                'avatar' => $providerUser->getAvatar(),
                'email_verified_at' => now(),
            ]);

            // Assign default subscriber role
            $subscriberRole = \App\Models\Role::where('slug', 'subscriber')->first();
            if ($subscriberRole) {
                $user->roles()->attach($subscriberRole->id);
            }
        }

        // Link provider to user
        UserProvider::create([
            'user_id' => $user->id,
            'provider_name' => $provider,
            'provider_id' => $providerUser->getId(),
        ]);

        return $user;
    }

    /**
     * Validate provider name
     */
    private function validateProvider(string $provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            throw new \Exception('Invalid provider. Only google and facebook are supported.');
        }
    }

    /**
     * Get authenticated user info
     */
    public function me()
    {
        return response()->json([
            'user' => auth()->user()->load('roles'),
        ]);
    }
}

