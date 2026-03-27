<?php

namespace App\Services\Payments;

use App\Models\Plan;
use App\Models\Subscription;
use InvalidArgumentException;

class PaymentCheckoutService
{
    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_SEPAY = 'sepay';

    public function __construct(
        private readonly StripePaymentService $stripePaymentService,
        private readonly SePayPaymentService $sePayPaymentService,
    ) {
    }

    public static function supportedProviders(): array
    {
        return [
            self::PROVIDER_STRIPE,
            self::PROVIDER_SEPAY,
        ];
    }

    public static function normalizeProvider(?string $provider): string
    {
        return in_array($provider, self::supportedProviders(), true)
            ? $provider
            : self::PROVIDER_STRIPE;
    }

    /**
     * @return array<string, mixed>
     */
    public function createCheckout(string $provider, Subscription $subscription, Plan $plan): array
    {
        return match ($provider) {
            self::PROVIDER_STRIPE => $this->stripePaymentService->createCheckout($subscription, $plan),
            self::PROVIDER_SEPAY => $this->sePayPaymentService->createCheckout($subscription, $plan),
            default => throw new InvalidArgumentException("Unsupported payment provider [{$provider}]"),
        };
    }
}
