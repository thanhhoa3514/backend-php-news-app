<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    use AuthorizesApiRequests;

    public function show(): JsonResponse
    {
        $user = $this->currentUser();

        return response()->json([
            'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
            'digest_frequency' => $user->digest_frequency,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $validated = $request->validate([
            'email_notifications_enabled' => 'sometimes|required|boolean',
            'digest_frequency' => 'sometimes|required|in:off,daily,weekly',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'preferences' => [
                'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
                'digest_frequency' => $user->digest_frequency,
            ],
        ]);
    }
}
