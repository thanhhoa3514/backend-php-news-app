<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OtpController extends Controller
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP to email
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        // Check if OTP already exists
        if ($this->otpService->hasActiveOtp($email)) {
            $remainingTime = $this->otpService->getRemainingTime($email);
            
            return response()->json([
                'success' => false,
                'message' => 'An OTP has already been sent to this email',
                'remaining_time' => $remainingTime,
                'remaining_time_formatted' => $this->formatTime($remainingTime)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Generate and send OTP
        $result = $this->otpService->generateAndSend($email);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'retry_after' => $result['retry_after'] ?? null
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'expires_in' => $result['expires_in'],
            'expires_at' => now()->addMinutes($result['expires_in'])->toDateTimeString()
        ], Response::HTTP_OK);
    }

    /**
     * Verify OTP
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $otp = $validated['otp'];

        // Verify OTP
        $isValid = $this->otpService->verify($email, $otp);

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'email' => $email,
            'verified_at' => now()->toDateTimeString()
        ], Response::HTTP_OK);
    }

    /**
     * Check OTP status
     */
    public function status(SendOtpRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        $hasActiveOtp = $this->otpService->hasActiveOtp($email);

        if (!$hasActiveOtp) {
            return response()->json([
                'success' => true,
                'has_active_otp' => false,
                'message' => 'No active OTP found for this email'
            ], Response::HTTP_OK);
        }

        $remainingTime = $this->otpService->getRemainingTime($email);

        return response()->json([
            'success' => true,
            'has_active_otp' => true,
            'remaining_time' => $remainingTime,
            'remaining_time_formatted' => $this->formatTime($remainingTime),
            'expires_at' => now()->addSeconds($remainingTime)->toDateTimeString()
        ], Response::HTTP_OK);
    }

    /**
     * Format time in seconds to readable format
     */
    private function formatTime(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%d minute%s %d second%s', 
                $minutes, 
                $minutes > 1 ? 's' : '', 
                $secs, 
                $secs !== 1 ? 's' : ''
            );
        }

        return sprintf('%d second%s', $secs, $secs !== 1 ? 's' : '');
    }
}

