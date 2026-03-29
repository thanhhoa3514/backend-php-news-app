<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Models\Subscription;
use App\Models\Plan;
use App\Services\NotificationDispatchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    use AuthorizesApiRequests;

    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {
    }

    /**
     * Display a listing of subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $userId = $request->get('user_id');
        $planId = $request->get('plan_id');

        $query = Subscription::with(['user', 'plan']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
            $userId = $user->id;
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($planId && $planId !== 'all') {
            $query->where('plan_id', $planId);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($subscriptions);
    }

    /**
     * Display the specified subscription
     */
    public function show(string $id): JsonResponse
    {
        $user = $this->currentUser();
        $subscription = Subscription::with(['user', 'plan'])->findOrFail($id);
        $this->ensureOwnerOrAdmin($subscription->user_id, $user, 'You can only view your own subscriptions.');

        return response()->json($subscription);
    }

    /**
     * Store a newly created subscription
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->ensureAdmin();
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'start_date' => 'nullable|date',
            'payment_method' => 'required|string|max:50',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        if (!isset($validated['start_date'])) {
            $validated['start_date'] = now();
        }

        $validated['end_date'] = now()->parse($validated['start_date'])->addDays($plan->duration_days);
        $validated['status'] = 'pending';

        $subscription = Subscription::create($validated);
        $subscription->load(['user', 'plan']);

        return response()->json($subscription, 201);
    }

    /**
     * Update the specified subscription
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureAdmin();
        $subscription = Subscription::findOrFail($id);
        $wasActive = $subscription->status === 'active';

        $validated = $request->validate([
            'status' => 'sometimes|required|in:active,expired,cancelled,pending',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|string|max:50',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        $subscription->update($validated);
        $subscription->load(['user', 'plan']);

        if (!$wasActive && $subscription->status === 'active') {
            $this->notificationDispatchService->notifyPremiumActivated($subscription);
        }

        return response()->json($subscription);
    }

    /**
     * Cancel the specified subscription
     */
    public function cancel(string $id): JsonResponse
    {
        $user = $this->currentUser();
        $subscription = Subscription::findOrFail($id);
        $this->ensureOwnerOrAdmin($subscription->user_id, $user, 'You can only cancel your own subscriptions.');

        if ($subscription->status === 'cancelled') {
            return response()->json([
                'message' => 'Subscription is already cancelled'
            ], 400);
        }

        $subscription->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'subscription' => $subscription
        ]);
    }

    /**
     * Activate the specified subscription
     */
    public function activate(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $subscription = Subscription::findOrFail($id);

        if ($subscription->status === 'active') {
            return response()->json([
                'message' => 'Subscription is already active'
            ], 400);
        }

        if ($subscription->hasExpired()) {
            return response()->json([
                'message' => 'Cannot activate expired subscription'
            ], 400);
        }

        $subscription->update(['status' => 'active']);
        $subscription->load(['user', 'plan']);
        $this->notificationDispatchService->notifyPremiumActivated($subscription);

        return response()->json([
            'message' => 'Subscription activated successfully',
            'subscription' => $subscription
        ]);
    }

    /**
     * Remove the specified subscription
     */
    public function destroy(string $id): JsonResponse
    {
        $this->ensureAdmin();
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'message' => 'Subscription deleted successfully'
        ]);
    }
}
