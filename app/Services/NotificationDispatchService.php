<?php

namespace App\Services;

use App\Models\News;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PremiumActivatedNotification;
use App\Notifications\PublishedNewsNotification;

class NotificationDispatchService
{
    public function notifyNewsPublished(News $news): void
    {
        $news->loadMissing(['category.followers', 'tags.followers', 'user', 'tags']);

        $followerIds = collect();

        if ($news->category) {
            $followerIds = $followerIds->merge($news->category->followers->pluck('id'));
        }

        foreach ($news->tags as $tag) {
            $tag->loadMissing('followers');
            $followerIds = $followerIds->merge($tag->followers->pluck('id'));
        }

        $recipientIds = $followerIds
            ->filter()
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $news->user_id)
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $recipientIds)
            ->get()
            ->each(fn (User $user) => $user->notify(new PublishedNewsNotification($news)));
    }

    public function notifyPremiumActivated(Subscription $subscription): void
    {
        $subscription->loadMissing(['user', 'plan']);

        if (!$subscription->user) {
            return;
        }

        $subscription->user->notify(new PremiumActivatedNotification($subscription));
    }
}
