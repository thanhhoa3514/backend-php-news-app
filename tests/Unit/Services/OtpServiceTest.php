<?php

namespace Tests\Unit\Services;

use App\Mail\OtpMail;
use App\Services\OtpService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    public function test_generate_and_send_returns_success_and_sends_mail(): void
    {
        config([
            'otp.length' => 6,
            'otp.expiry' => 10,
            'otp.max_attempts' => 3,
        ]);

        $email = 'user@example.com';

        Redis::shouldReceive('get')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(0);

        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function (string $key, int $ttl, string $otp): bool {
                return $key === 'otp:user@example.com'
                    && $ttl === 600
                    && preg_match('/^\d{6}$/', $otp) === 1;
            })
            ->andReturnTrue();

        Redis::shouldReceive('exists')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(false);

        Redis::shouldReceive('setex')
            ->once()
            ->with('otp_attempts:'.$email, 3600, 1)
            ->andReturnTrue();

        Mail::shouldReceive('to')
            ->once()
            ->with($email)
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->once()
            ->with(Mockery::type(OtpMail::class))
            ->andReturnNull();

        $service = new OtpService();
        $result = $service->generateAndSend($email);

        $this->assertTrue($result['success']);
        $this->assertSame('OTP sent successfully to your email', $result['message']);
        $this->assertSame(10, $result['expires_in']);
    }

    public function test_generate_and_send_blocks_when_attempt_limit_reached(): void
    {
        config([
            'otp.max_attempts' => 3,
        ]);

        $email = 'blocked@example.com';

        Redis::shouldReceive('get')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(3);

        Redis::shouldReceive('ttl')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(120);

        Mail::shouldReceive('to')->never();
        Mail::shouldReceive('send')->never();

        $service = new OtpService();
        $result = $service->generateAndSend($email);

        $this->assertFalse($result['success']);
        $this->assertSame('Too many OTP requests. Please try again later.', $result['message']);
        $this->assertSame(120, $result['retry_after']);
    }

    public function test_generate_and_send_rolls_back_when_mail_send_fails(): void
    {
        config([
            'otp.length' => 6,
            'otp.expiry' => 10,
            'otp.max_attempts' => 3,
        ]);

        $email = 'rollback@example.com';

        Redis::shouldReceive('get')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(1);

        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function (string $key, int $ttl, string $otp) use ($email): bool {
                return $key === 'otp:'.$email
                    && $ttl === 600
                    && preg_match('/^\d{6}$/', $otp) === 1;
            })
            ->andReturnTrue();

        Redis::shouldReceive('exists')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(false);

        Redis::shouldReceive('setex')
            ->once()
            ->with('otp_attempts:'.$email, 3600, 1)
            ->andReturnTrue();

        Mail::shouldReceive('to')
            ->once()
            ->with($email)
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->once()
            ->with(Mockery::type(OtpMail::class))
            ->andThrow(new \RuntimeException('SMTP down'));

        Redis::shouldReceive('del')
            ->once()
            ->with('otp:'.$email)
            ->andReturn(1);

        Redis::shouldReceive('exists')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(true);

        Redis::shouldReceive('decr')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(0);

        $service = new OtpService();
        $result = $service->generateAndSend($email);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to send OTP email', $result['message']);
    }

    public function test_verify_returns_true_and_clears_otp_and_attempts_for_valid_code(): void
    {
        $email = 'valid@example.com';
        $otp = '123456';

        Redis::shouldReceive('get')
            ->once()
            ->with('otp:'.$email)
            ->andReturn($otp);

        Redis::shouldReceive('del')
            ->once()
            ->with('otp:'.$email)
            ->andReturn(1);

        Redis::shouldReceive('del')
            ->once()
            ->with('otp_attempts:'.$email)
            ->andReturn(1);

        $service = new OtpService();

        $this->assertTrue($service->verify($email, $otp));
    }

    public function test_verify_returns_false_for_invalid_code(): void
    {
        $email = 'invalid@example.com';

        Redis::shouldReceive('get')
            ->once()
            ->with('otp:'.$email)
            ->andReturn('123456');

        Redis::shouldReceive('del')->never();

        $service = new OtpService();

        $this->assertFalse($service->verify($email, '000000'));
    }

    public function test_registration_data_round_trip_and_clear(): void
    {
        $email = 'pending@example.com';
        $payload = ['name' => 'Jane', 'plan' => 'premium'];
        $encoded = json_encode($payload);

        Redis::shouldReceive('setex')
            ->once()
            ->with('registration_pending:'.$email, 300, $encoded)
            ->andReturnTrue();

        Redis::shouldReceive('get')
            ->once()
            ->with('registration_pending:'.$email)
            ->andReturn($encoded);

        Redis::shouldReceive('del')
            ->once()
            ->with('registration_pending:'.$email)
            ->andReturn(1);

        $service = new OtpService();
        $service->storeRegistrationData($email, $payload);
        $stored = $service->getRegistrationData($email);
        $service->clearRegistrationData($email);

        $this->assertSame($payload, $stored);
    }

    public function test_has_active_otp_and_remaining_time_use_redis_values(): void
    {
        $email = 'active@example.com';

        Redis::shouldReceive('exists')
            ->once()
            ->with('otp:'.$email)
            ->andReturn(1);

        Redis::shouldReceive('ttl')
            ->once()
            ->with('otp:'.$email)
            ->andReturn(45);

        $service = new OtpService();

        $this->assertTrue($service->hasActiveOtp($email));
        $this->assertSame(45, $service->getRemainingTime($email));
    }
}

