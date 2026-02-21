<?php

namespace App\Observers;

use App\Models\News;
use Illuminate\Support\Facades\Cache;

class NewsObserver
{
    /**
     * Clear news related caches
     */
    private function clearCache(): void
    {
        Cache::forget('news.published');
        Cache::forget('categories.all'); // since news count might change
    }

    /**
     * Handle the News "created" event.
     */
    public function created(News $news): void
    {
        $this->clearCache();
    }

    /**
     * Handle the News "updated" event.
     */
    public function updated(News $news): void
    {
        $this->clearCache();
        Cache::forget('news.detail.' . $news->id);
    }

    /**
     * Handle the News "deleted" event.
     */
    public function deleted(News $news): void
    {
        $this->clearCache();
        Cache::forget('news.detail.' . $news->id);
    }

    /**
     * Handle the News "restored" event.
     */
    public function restored(News $news): void
    {
        $this->clearCache();
    }

    /**
     * Handle the News "force deleted" event.
     */
    public function forceDeleted(News $news): void
    {
        $this->clearCache();
        Cache::forget('news.detail.' . $news->id);
    }
}
