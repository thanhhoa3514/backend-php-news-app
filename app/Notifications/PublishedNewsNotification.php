<?php

namespace App\Notifications;

use App\Models\News;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PublishedNewsNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly News $news,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $categoryName = $this->news->category?->name ?? 'General';
        $title = $this->news->isPremium()
            ? 'New premium article available'
            : 'New article available';

        return [
            'type' => 'news_published',
            'title' => $title,
            'message' => sprintf('%s in %s', $this->news->title, $categoryName),
            'link' => '/news/' . $this->news->id,
            'news_id' => $this->news->id,
            'category_id' => $this->news->category_id,
            'tag_ids' => $this->news->tags->pluck('id')->values()->all(),
            'is_premium' => (bool) $this->news->is_premium,
            'published_at' => optional($this->news->published_at)->toISOString(),
        ];
    }
}
