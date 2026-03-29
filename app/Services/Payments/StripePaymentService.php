<?php

namespace App\Services\Payments;

use App\Models\Plan;
use App\Models\Subscription;
use RuntimeException;

class StripePaymentService
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(Subscription $subscription, Plan $plan): array
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            throw new RuntimeException('STRIPE_SECRET is not configured.');
        }

        \Stripe\Stripe::setApiKey($secret);
        $frontendUrl = rtrim((string) config('services.stripe.frontend_url', 'https://monochrome-news.vercel.app'), '/');

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $plan->name,
                        'description' => $plan->description ?? 'Subscription Plan',
                    ],
                    'unit_amount' => (int) round(((float) $plan->price) * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/payment-success?provider=stripe&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl . '/checkout/' . $plan->id . '?canceled=true&provider=stripe',
            'metadata' => [
                'provider' => PaymentCheckoutService::PROVIDER_STRIPE,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'user_id' => $subscription->user_id,
            ],
        ]);

        return [
            'provider' => PaymentCheckoutService::PROVIDER_STRIPE,
            'mode' => 'redirect',
            'checkoutUrl' => $session->url,
            'sessionId' => $session->id,
            'subscriptionId' => $subscription->id,
            'transactionId' => $session->id,
        ];
    }
}
