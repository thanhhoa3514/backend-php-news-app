<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PremiumActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Subscription $subscription,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'premium_activated',
            'title' => 'Premium access activated',
            'message' => sprintf(
                'Your %s plan is now active.',
                $this->subscription->plan?->name ?? 'subscription'
            ),
            'link' => '/pricing',
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'end_date' => optional($this->subscription->end_date)->toISOString(),
        ];
    }
}
