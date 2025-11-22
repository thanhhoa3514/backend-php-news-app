<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'thumbnail',
        'user_id',
        'category_id',
        'published_at',
        'is_premium',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_premium' => 'boolean',
        ];
    }

    /**
     * Get the category for this news
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the author for this news
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tags for this news
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'news_tags');
    }

    /**
     * Scope a query to only include published news
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include premium news
     */
    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope a query to only include free news
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope a query to search news
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    /**
     * Check if news is published
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null 
            && $this->published_at <= now();
    }

    /**
     * Check if news is premium
     */
    public function isPremium(): bool
    {
        return $this->is_premium;
    }
}

