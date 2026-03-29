<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get all news for this category
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    /**
     * Get published news for this category
     */
    public function publishedNews(): HasMany
    {
        return $this->hasMany(News::class)
            ->where('status', 'published')
            ->orderBy('published_at', 'desc');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'category_user_follows')->withTimestamps();
    }
}
