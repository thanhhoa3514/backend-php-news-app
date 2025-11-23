<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'prompt',
        'generated_content',
        'status'
    ];

    protected $casts = [
        'generated_content' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
