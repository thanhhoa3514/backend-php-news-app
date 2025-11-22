<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            UserSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            TagSeeder::class,
            PlanSeeder::class,
            NewsSeeder::class,
        ]);
    }
}

