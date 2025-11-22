<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class OtpService
{
    private const OTP_PREFIX = 'otp:';
    private const OTP_ATTEMPTS_PREFIX = 'otp_attempts:';
    private const REGISTRATION_PREFIX = 'registration_pending:';
    private int $otpLength;
    private int $otpExpiry; // minutes
    private int $maxAttempts;

    public function __construct()
    {
        $this->otpLength = config('otp.length', 6);
        $this->otpExpiry = config('otp.expiry', 10);
        $this->maxAttempts = config('otp.max_attempts', 3);
    }

    /**
     * Generate and send OTP to email
     */
    public function generateAndSend(string $email): array
    {
        // Check if too many attempts
        if ($this->hasExceededAttempts($email)) {
            return [
                'success' => false,
                'message' => 'Too many OTP requests. Please try again later.',
                'retry_after' => $this->getRetryAfter($email)
            ];
        }

        // Generate OTP
        $otp = $this->generateOtp();

        // Store in Redis with expiry
        $this->storeOtp($email, $otp);

        // Increment attempts
        $this->incrementAttempts($email);

        // Send email
        try {
            Mail::to($email)->send(new OtpMail($otp, $this->otpExpiry));
            
            return [
                'success' => true,
                'message' => 'OTP sent successfully to your email',
                'expires_in' => $this->otpExpiry
            ];
        } catch (\Exception $e) {
            // Rollback: Delete OTP and decrement attempts so user isn't locked out
            $this->deleteOtp($email);
            $this->decrementAttempts($email);

            return [
                'success' => false,
                'message' => 'Failed to send OTP email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP
     */
    public function verify(string $email, string $otp): bool
    {
        $storedOtp = $this->getOtp($email);

        if (!$storedOtp) {
            return false;
        }

        if ($storedOtp === $otp) {
            // OTP is correct, delete it
            $this->deleteOtp($email);
            $this->resetAttempts($email);
            return true;
        }

        return false;
    }

    /**
     * Generate random OTP
     */
    private function generateOtp(): string
    {
        $otp = '';
        for ($i = 0; $i < $this->otpLength; $i++) {
            $otp .= random_int(0, 9);
        }
        return $otp;
    }

    /**
     * Store OTP in Redis
     */
    private function storeOtp(string $email, string $otp): void
    {
        $key = self::OTP_PREFIX . $email;
        Redis::setex($key, $this->otpExpiry * 60, $otp);
    }

    /**
     * Get OTP from Redis
     */
    private function getOtp(string $email): ?string
    {
        $key = self::OTP_PREFIX . $email;
        $otp = Redis::get($key);
        return $otp ? (string) $otp : null;
    }

    /**
     * Delete OTP from Redis
     */
    private function deleteOtp(string $email): void
    {
        $key = self::OTP_PREFIX . $email;
        Redis::del($key);
    }

    /**
     * Check if user has exceeded max attempts
     */
    private function hasExceededAttempts(string $email): bool
    {
        $attempts = $this->getAttempts($email);
        return $attempts >= $this->maxAttempts;
    }

    /**
     * Get current attempts count
     */
    private function getAttempts(string $email): int
    {
        $key = self::OTP_ATTEMPTS_PREFIX . $email;
        $attempts = Redis::get($key);
        return $attempts ? (int) $attempts : 0;
    }

    /**
     * Increment attempts counter
     */
    private function incrementAttempts(string $email): void
    {
        $key = self::OTP_ATTEMPTS_PREFIX . $email;
        
        if (!Redis::exists($key)) {
            // Set with 1 hour expiry
            Redis::setex($key, 3600, 1);
        } else {
            Redis::incr($key);
        }
    }

    /**
     * Decrement attempts counter (used for rollback)
     */
    private function decrementAttempts(string $email): void
    {
        $key = self::OTP_ATTEMPTS_PREFIX . $email;
        if (Redis::exists($key)) {
            Redis::decr($key);
        }
    }

    /**
     * Reset attempts counter
     */
    private function resetAttempts(string $email): void
    {
        $key = self::OTP_ATTEMPTS_PREFIX . $email;
        Redis::del($key);
    }

    /**
     * Get retry after time in seconds
     */
    private function getRetryAfter(string $email): int
    {
        $key = self::OTP_ATTEMPTS_PREFIX . $email;
        $ttl = Redis::ttl($key);
        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * Check if OTP exists for email
     */
    public function hasActiveOtp(string $email): bool
    {
        $key = self::OTP_PREFIX . $email;
        return Redis::exists($key) > 0;
    }

    /**
     * Get remaining time for OTP
     */
    public function getRemainingTime(string $email): int
    {
        $key = self::OTP_PREFIX . $email;
        $ttl = Redis::ttl($key);
        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * Store registration data temporarily in Redis
     */
    public function storeRegistrationData(string $email, array $data, int $ttl = 300): void
    {
        $key = self::REGISTRATION_PREFIX . $email;
        Redis::setex($key, $ttl, json_encode($data));
    }

    /**
     * Get registration data from Redis
     */
    public function getRegistrationData(string $email): ?array
    {
        $key = self::REGISTRATION_PREFIX . $email;
        $data = Redis::get($key);
        
        if (!$data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Clear registration data from Redis
     */
    public function clearRegistrationData(string $email): void
    {
        $key = self::REGISTRATION_PREFIX . $email;
        Redis::del($key);
    }
}

