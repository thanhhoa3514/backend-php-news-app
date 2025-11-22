<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Access to basic content and limited articles per month',
                'price' => 0.00,
                'duration_days' => 30,
                'access_limit' => 5,
                'status' => 'active',
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'Access to all standard content with unlimited articles',
                'price' => 9.99,
                'duration_days' => 30,
                'access_limit' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Full access to all premium content, exclusive articles, and early access to new content',
                'price' => 19.99,
                'duration_days' => 30,
                'access_limit' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Annual Standard',
                'slug' => 'annual-standard',
                'description' => 'Annual subscription with standard access at a discounted rate',
                'price' => 99.99,
                'duration_days' => 365,
                'access_limit' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Annual Premium',
                'slug' => 'annual-premium',
                'description' => 'Annual subscription with premium access at a discounted rate',
                'price' => 199.99,
                'duration_days' => 365,
                'access_limit' => null,
                'status' => 'active',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}

