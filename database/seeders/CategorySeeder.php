<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Technology',
                'slug' => 'technology',
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
            ],
            [
                'name' => 'Environment',
                'slug' => 'environment',
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
            ],
            [
                'name' => 'Culture',
                'slug' => 'culture',
            ],
            [
                'name' => 'Politics',
                'slug' => 'politics',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}

