<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\News;
use App\Models\Tag;

class NewsTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all news and tags
        $newsArticles = News::all();
        $tags = Tag::all();

        if ($newsArticles->isEmpty() || $tags->isEmpty()) {
            $this->command->warn('No news articles or tags found. Please seed news and tags first.');
            return;
        }

        $this->command->info('Attaching tags to news articles...');

        // Attach random tags to each news article (2-5 tags per article)
        foreach ($newsArticles as $news) {
            // Random number of tags between 2 and 5
            $numberOfTags = rand(2, min(5, $tags->count()));
            
            // Get random tags
            $randomTags = $tags->random($numberOfTags)->pluck('id');
            
            // Attach tags to news article
            $news->tags()->sync($randomTags);
        }

        $this->command->info('Successfully attached tags to ' . $newsArticles->count() . ' news articles.');
    }
}

