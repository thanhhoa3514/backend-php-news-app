<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\NotificationDispatchService;
use App\Services\Payments\SePayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly SePayPaymentService $sePayPaymentService,
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {
    }

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
                    $subscription = Subscription::with('plan')->find($subscriptionId);
                    if ($subscription) {
                        $this->activateSubscription($subscription, $session->id);
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

    public function handleSePayWebhook(Request $request)
    {
        if (!$this->sePayPaymentService->verifyWebhookAuthorization($request->header('Authorization'))) {
            Log::warning('SePay webhook authorization failed.');

            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        if (($payload['transferType'] ?? null) !== 'in') {
            return response()->json(['success' => true, 'message' => 'Ignored transfer type']);
        }

        $subscriptionId = $this->sePayPaymentService->extractSubscriptionIdFromContent($payload['content'] ?? $payload['code'] ?? null);

        if (!$subscriptionId) {
            Log::warning('SePay webhook did not include a recognizable subscription reference.', [
                'content' => $payload['content'] ?? null,
                'code' => $payload['code'] ?? null,
                'referenceCode' => $payload['referenceCode'] ?? null,
            ]);

            return response()->json(['success' => true, 'message' => 'No matching subscription reference']);
        }

        $subscription = Subscription::with('plan')->find($subscriptionId);

        if (!$subscription) {
            Log::warning('SePay webhook referenced a missing subscription.', [
                'subscription_id' => $subscriptionId,
            ]);

            return response()->json(['success' => true, 'message' => 'Subscription not found']);
        }

        if ($subscription->payment_method !== 'sepay') {
            Log::warning('SePay webhook matched a subscription owned by another provider.', [
                'subscription_id' => $subscriptionId,
                'payment_method' => $subscription->payment_method,
            ]);

            return response()->json(['success' => true, 'message' => 'Provider mismatch']);
        }

        $transferAmount = (int) ($payload['transferAmount'] ?? 0);
        $expectedAmount = $this->sePayPaymentService->expectedAmountVnd($subscription->plan);

        if ($transferAmount !== $expectedAmount) {
            Log::warning('SePay webhook amount mismatch.', [
                'subscription_id' => $subscriptionId,
                'expected_amount' => $expectedAmount,
                'received_amount' => $transferAmount,
            ]);

            return response()->json(['success' => true, 'message' => 'Amount mismatch ignored']);
        }

        if ($subscription->status === 'active') {
            return response()->json(['success' => true, 'message' => 'Subscription already active']);
        }

        $providerTransactionId = $payload['referenceCode']
            ?? ($payload['id'] ?? $subscription->transaction_id);

        $this->activateSubscription($subscription, is_scalar($providerTransactionId) ? (string) $providerTransactionId : null);

        Log::info('Subscription activated via SePay webhook.', [
            'subscription_id' => $subscriptionId,
            'reference_code' => $payload['referenceCode'] ?? null,
            'transaction_id' => $payload['id'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    private function activateSubscription(Subscription $subscription, ?string $providerTransactionId = null): void
    {
        $subscription->loadMissing('plan');

        $startDate = now();
        $endDate = $subscription->plan
            ? (clone $startDate)->addDays($subscription->plan->duration_days)
            : $subscription->end_date;

        $subscription->update([
            'status' => 'active',
            'transaction_id' => $providerTransactionId ?? $subscription->transaction_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $subscription->loadMissing(['user', 'plan']);
        $this->notificationDispatchService->notifyPremiumActivated($subscription);
    }
}
