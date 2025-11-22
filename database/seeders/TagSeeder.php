<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => 'Breaking News',
                'slug' => 'breaking-news',
            ],
            [
                'name' => 'Trending',
                'slug' => 'trending',
            ],
            [
                'name' => 'Innovation',
                'slug' => 'innovation',
            ],
            [
                'name' => 'Analysis',
                'slug' => 'analysis',
            ],
            [
                'name' => 'Opinion',
                'slug' => 'opinion',
            ],
            [
                'name' => 'Research',
                'slug' => 'research',
            ],
            [
                'name' => 'Interview',
                'slug' => 'interview',
            ],
            [
                'name' => 'Global',
                'slug' => 'global',
            ],
            [
                'name' => 'Local',
                'slug' => 'local',
            ],
            [
                'name' => 'Featured',
                'slug' => 'featured',
            ],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}

