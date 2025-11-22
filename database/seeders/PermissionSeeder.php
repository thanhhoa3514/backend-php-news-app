<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rolesData = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator with full access',
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Editor can create and manage content',
            ],
            [
                'name' => 'Author',
                'slug' => 'author',
                'description' => 'Author can create and edit own content',
            ],
            [
                'name' => 'Subscriber',
                'slug' => 'subscriber',
                'description' => 'Subscriber can access premium content',
            ],
        ];

        $roles = [];
        foreach ($rolesData as $roleData) {
            $roles[$roleData['slug']] = Role::create($roleData);
        }

        $permissions = [
            [
                'name' => 'Create News',
                'slug' => 'create-news',
                'description' => 'Ability to create new news articles',
            ],
            [
                'name' => 'Edit News',
                'slug' => 'edit-news',
                'description' => 'Ability to edit news articles',
            ],
            [
                'name' => 'Delete News',
                'slug' => 'delete-news',
                'description' => 'Ability to delete news articles',
            ],
            [
                'name' => 'Publish News',
                'slug' => 'publish-news',
                'description' => 'Ability to publish news articles',
            ],
            [
                'name' => 'Manage Users',
                'slug' => 'manage-users',
                'description' => 'Ability to create, edit, and delete users',
            ],
            [
                'name' => 'Manage Roles',
                'slug' => 'manage-roles',
                'description' => 'Ability to assign and modify user roles',
            ],
            [
                'name' => 'Manage Categories',
                'slug' => 'manage-categories',
                'description' => 'Ability to manage news categories',
            ],
            [
                'name' => 'Manage Tags',
                'slug' => 'manage-tags',
                'description' => 'Ability to manage news tags',
            ],
            [
                'name' => 'View Premium Content',
                'slug' => 'view-premium-content',
                'description' => 'Ability to view premium news articles',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        $adminRole = $roles['admin'];
        $editorRole = $roles['editor'];
        $authorRole = $roles['author'];
        $subscriberRole = $roles['subscriber'];

        $adminRole->permissions()->attach(Permission::all());
        
        $editorRole->permissions()->attach(
            Permission::whereIn('slug', [
                'create-news',
                'edit-news',
                'publish-news',
                'manage-categories',
                'manage-tags',
            ])->get()
        );

        $authorRole->permissions()->attach(
            Permission::whereIn('slug', [
                'create-news',
                'edit-news',
            ])->get()
        );

        $subscriberRole->permissions()->attach(
            Permission::where('slug', 'view-premium-content')->get()
        );
    }
}

