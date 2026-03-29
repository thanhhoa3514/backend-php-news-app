<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Payments\PaymentCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentCheckoutService $paymentCheckoutService,
    ) {
    }

    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'provider' => 'nullable|in:stripe,sepay',
        ]);

        $provider = PaymentCheckoutService::normalizeProvider($validated['provider'] ?? null);
        $plan = Plan::findOrFail($validated['plan_id']);

        if ((float) $plan->price <= 0) {
            return response()->json([
                'message' => 'This plan does not require paid checkout.',
            ], 422);
        }

        $subscription = Subscription::create([
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => now()->addDays($plan->duration_days),
            'status' => 'pending',
            'payment_method' => $provider,
        ]);

        try {
            $checkout = $this->paymentCheckoutService->createCheckout($provider, $subscription, $plan);

            if (isset($checkout['transactionId']) && is_string($checkout['transactionId'])) {
                $subscription->update(['transaction_id' => $checkout['transactionId']]);
            }

            return response()->json($checkout);
        } catch (\Throwable $e) {
            $subscription->delete();
            Log::error('Payment checkout creation failed', [
                'provider' => $provider,
                'plan_id' => $plan->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create checkout session',
                'provider' => $provider,
            ], 500);
        }
    }
}
