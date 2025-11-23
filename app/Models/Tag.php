<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the news articles with this tag
     */
    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_tag');
    }
}

