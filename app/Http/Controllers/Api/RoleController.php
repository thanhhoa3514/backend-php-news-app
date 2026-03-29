<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    use AuthorizesApiRequests;

    /**
     * Display a listing of roles
     */
    public function index(): JsonResponse
    {
        $this->ensureAdmin();
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    /**
     * Display the specified role
     */
    public function show(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $role = Role::with('permissions')
            ->withCount('users')
            ->findOrFail($id);

        return response()->json($role);
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $permissions = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $role = Role::create($validated);

        if (!empty($permissions)) {
            $role->permissions()->attach($permissions);
        }

        $role->load('permissions');

        return response()->json($role, 201);
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureAdmin();
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissions = $validated['permissions'] ?? null;
        unset($validated['permissions']);

        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $role->update($validated);

        if ($permissions !== null) {
            $role->permissions()->sync($permissions);
        }

        $role->load('permissions');

        return response()->json($role);
    }

    /**
     * Remove the specified role
     */
    public function destroy(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $role = Role::findOrFail($id);
        
        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete role with assigned users'
            ], 400);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get users with this role
     */
    public function users(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $role = Role::findOrFail($id);
        $users = $role->users()->with('roles')->paginate(15);

        return response()->json($users);
    }
}
