<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use AuthorizesApiRequests;

    /**
     * Public teacher demo: read-only user listing
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);

        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Public teacher demo: read-only user detail
     */
    public function publicShow(string $id): JsonResponse
    {
        $user = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $perPage = $request->get('per_page', 10);
        $roleSlug = $request->get('role');

        $query = User::with('roles')->withCount('news');

        if ($roleSlug) {
            $query->whereHas('roles', function ($q) use ($roleSlug) {
                $q->where('slug', $roleSlug);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Display the specified user
     */
    public function show(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $user = User::with(['roles', 'subscriptions.plan'])
            ->withCount('news')
            ->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|string|max:500',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if (!empty($roles)) {
            $user->roles()->attach($roles);
        }

        $user->load('roles');

        return response()->json($user, 201);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureAdmin();
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
            'avatar' => 'nullable|string|max:500',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roles = $validated['roles'] ?? null;
        unset($validated['roles']);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if ($roles !== null) {
            $user->roles()->sync($roles);
        }

        $user->load('roles');

        return response()->json($user);
    }

    /**
     * Remove the specified user
     */
    public function destroy(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $user = User::findOrFail($id);
        
        // Don't allow deleting users with published articles
        if ($user->news()->whereNotNull('published_at')->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete user with published articles'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get user roles
     */
    public function roles(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $user = User::findOrFail($id);
        $roles = $user->roles()->with('permissions')->get();

        return response()->json($roles);
    }
}
