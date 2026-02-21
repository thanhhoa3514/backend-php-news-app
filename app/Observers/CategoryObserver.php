<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    /**
     * Clear category related caches
     */
    private function clearCache(?Category $category = null): void
    {
        Cache::forget('categories.all');
        if ($category) {
            Cache::forget('category.detail.' . $category->slug);
        }
    }

    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        if($category->wasChanged()){
            Cache::forget('category.detail.' . $category->getOriginal('slug'));
        }
        $this->clearCache($category);
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->clearCache($category);
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->clearCache($category);
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        $this->clearCache($category);
    }
}
