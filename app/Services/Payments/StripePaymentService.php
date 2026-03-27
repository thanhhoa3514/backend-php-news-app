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
        $secret = env('STRIPE_SECRET');

        if (!$secret) {
            throw new RuntimeException('STRIPE_SECRET is not configured.');
        }

        \Stripe\Stripe::setApiKey($secret);

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
            'success_url' => rtrim(env('FRONTEND_URL', 'https://monochrome-news.vercel.app'), '/') . '/payment-success?provider=stripe&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => rtrim(env('FRONTEND_URL', 'https://monochrome-news.vercel.app'), '/') . '/checkout/' . $plan->id . '?canceled=true&provider=stripe',
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
