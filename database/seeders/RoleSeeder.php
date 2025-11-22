<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        $adminRole = Role::where('slug', 'admin')->first();
        $editorRole = Role::where('slug', 'editor')->first();
        $authorRole = Role::where('slug', 'author')->first();
        $subscriberRole = Role::where('slug', 'subscriber')->first();

        if ($users->count() > 0 && $adminRole) {
            $users[0]->roles()->attach($adminRole->id);
        }

        if ($users->count() > 1 && $editorRole) {
            $users[1]->roles()->attach($editorRole->id);
            if ($users->count() > 2) {
                $users[2]->roles()->attach($editorRole->id);
            }
        }

        if ($users->count() > 3 && $authorRole) {
            $users[3]->roles()->attach($authorRole->id);
            if ($users->count() > 4) {
                $users[4]->roles()->attach($authorRole->id);
            }
        }

        if ($users->count() > 5 && $subscriberRole) {
            for ($i = 5; $i < $users->count(); $i++) {
                $users[$i]->roles()->attach($subscriberRole->id);
            }
        }
    }
}

