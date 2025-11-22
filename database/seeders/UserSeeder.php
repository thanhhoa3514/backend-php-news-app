<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=1',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=11',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=5',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=12',
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=9',
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david.wilson@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=13',
            ],
            [
                'name' => 'Jessica Martinez',
                'email' => 'jessica.martinez@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=20',
            ],
            [
                'name' => 'James Anderson',
                'email' => 'james.anderson@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=14',
            ],
            [
                'name' => 'Emma Taylor',
                'email' => 'emma.taylor@example.com',
                'password' => Hash::make('password'),
                'avatar' => 'https://i.pravatar.cc/150?img=23',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}

