<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook Signature Verification Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid Signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook Error'], 400);
        }

        // Xử lý sự kiện
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                
                // Lấy subscription_id từ metadata
                $subscriptionId = $session->metadata->subscription_id ?? null;
                
                if ($subscriptionId && $session->payment_status === 'paid') {
                    $subscription = Subscription::find($subscriptionId);
                    if ($subscription) {
                        $plan = Plan::find($subscription->plan_id);
                        $startDate = now();
                        $endDate = $plan
                            ? (clone $startDate)->addDays($plan->duration_days)
                            : $subscription->end_date;

                        $subscription->update([
                            'status' => 'active',
                            'transaction_id' => $session->id,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ]);
                        Log::info("Subscription activated via Checkout: ID=" . $subscriptionId);
                    }
                }
                break;

            case 'checkout.session.expired':
                $session = $event->data->object;
                $subscriptionId = $session->metadata->subscription_id ?? null;
                
                if ($subscriptionId) {
                    Subscription::where('id', $subscriptionId)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled']);
                    Log::info("Checkout session expired, subscription cancelled: " . $subscriptionId);
                }
                break;

            default:
                Log::info("Unhandled Stripe event type: " . $event->type);
        }

        return response()->json(['status' => 'success']);
    }
}
