<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    /**
     * Display a listing of plans
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');

        $query = Plan::withCount('subscriptions');

        if ($status) {
            $query->where('status', $status);
        }

        $plans = $query->orderBy('price')->get();

        return response()->json($plans);
    }

    /**
     * Display the specified plan
     */
    public function show(string $id): JsonResponse
    {
        $plan = Plan::withCount('subscriptions')->findOrFail($id);

        return response()->json($plan);
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'access_limit' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if (!isset($validated['status'])) {
            $validated['status'] = 'active';
        }

        $plan = Plan::create($validated);

        return response()->json($plan, 201);
    }

    /**
     * Update the specified plan
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:plans,slug,' . $id,
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration_days' => 'sometimes|required|integer|min:1',
            'access_limit' => 'nullable|integer|min:0',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $plan->update($validated);

        return response()->json($plan);
    }

    /**
     * Remove the specified plan
     */
    public function destroy(string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        
        if ($plan->subscriptions()->where('status', 'active')->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete plan with active subscriptions'
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted successfully'
        ]);
    }
}

