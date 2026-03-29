<?php

namespace App\Services;

use App\Models\Subscription;

class SubscriptionActivationService
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {
    }

    public function activate(Subscription $subscription, ?string $providerTransactionId = null): Subscription
    {
        if ($subscription->status === 'active') {
            return $subscription->loadMissing(['user', 'plan']);
        }

        $subscription->loadMissing(['plan', 'user']);

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

        return $subscription;
    }
}
