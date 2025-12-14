<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        // Set Stripe API key
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        // 1. Validate dữ liệu
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        // 2. Lấy giá tiền từ Database
        $plan = Plan::findOrFail($request->plan_id);
        $amount = (int)($plan->price * 100); // Stripe tính bằng cent

        // 3. Tạo Subscription với status pending
        $subscription = Subscription::create([
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => now()->addDays($plan->duration_days),
            'status' => 'pending',
            'payment_method' => 'stripe',
        ]);

        // 4. Tạo Stripe Checkout Session
        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => $plan->description ?? 'Subscription Plan',
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('FRONTEND_URL', 'http://localhost:8080') . '/payment-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('FRONTEND_URL', 'http://localhost:8080') . '/checkout/' . $plan->id . '?canceled=true',
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'user_id' => Auth::id(),
                ],
            ]);

            // 5. Lưu session_id vào subscription
            $subscription->update(['transaction_id' => $session->id]);

            // 6. Trả về URL để redirect
            return response()->json([
                'checkoutUrl' => $session->url,
                'sessionId' => $session->id,
                'subscriptionId' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            // Nếu tạo Session thất bại, xóa subscription pending
            $subscription->delete();
            Log::error('Stripe Checkout Session Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}