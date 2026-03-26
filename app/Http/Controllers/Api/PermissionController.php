<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    use AuthorizesApiRequests;

    /**
     * Display a listing of permissions
     */
    public function index(): JsonResponse
    {
        $this->ensureAdmin();
        $permissions = Permission::with('roles')->orderBy('name')->get();

        return response()->json($permissions);
    }

    /**
     * Display the specified permission
     */
    public function show(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $permission = Permission::with('roles')->findOrFail($id);

        return response()->json($permission);
    }

    /**
     * Get permissions for a specific role
     */
    public function byRole(string $roleId): JsonResponse
    {
        $this->ensureAdmin();
        $permissions = Permission::whereHas('roles', function ($query) use ($roleId) {
            $query->where('roles.id', $roleId);
        })->get();

        return response()->json($permissions);
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:permissions,slug',
            'description' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $permission = Permission::create($validated);

        if (!empty($roles)) {
            $permission->roles()->attach($roles);
        }

        $permission->load('roles');

        return response()->json($permission, 201);
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureAdmin();
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:permissions,slug,' . $id,
            'description' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roles = $validated['roles'] ?? null;
        unset($validated['roles']);

        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $permission->update($validated);

        if ($roles !== null) {
            $permission->roles()->sync($roles);
        }

        $permission->load('roles');

        return response()->json($permission);
    }

    /**
     * Remove the specified permission
     */
    public function destroy(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ]);
    }
}
