<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $notifications->getCollection()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? null,
                    'link' => $notification->data['link'] ?? null,
                    'data' => $notification->data,
                    'read_at' => optional($notification->read_at)->toISOString(),
                    'created_at' => optional($notification->created_at)->toISOString(),
                ];
            }),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function digestPreview(Request $request): JsonResponse
    {
        $user = $this->currentUser()->loadMissing([
            'followedCategories:id',
            'followedTags:id',
        ]);

        $frequency = $request->get('frequency', $user->digest_frequency ?? 'weekly');
        if (!in_array($frequency, ['off', 'daily', 'weekly'], true)) {
            $frequency = 'weekly';
        }

        if ($frequency === 'off') {
            return response()->json([
                'frequency' => 'off',
                'generated_at' => now()->toISOString(),
                'window_start' => null,
                'articles' => [],
            ]);
        }

        $windowStart = $this->resolveDigestWindowStart($frequency);
        $categoryIds = $user->followedCategories->pluck('id')->all();
        $tagIds = $user->followedTags->pluck('id')->all();

        if (empty($categoryIds) && empty($tagIds)) {
            return response()->json([
                'frequency' => $frequency,
                'generated_at' => now()->toISOString(),
                'window_start' => $windowStart->toISOString(),
                'articles' => [],
            ]);
        }

        $limit = min(max((int) $request->get('limit', 8), 1), 20);

        $articles = News::query()
            ->with(['category', 'tags', 'user'])
            ->published()
            ->where('published_at', '>=', $windowStart)
            ->where(function ($query) use ($categoryIds, $tagIds) {
                if (!empty($categoryIds)) {
                    $query->whereIn('category_id', $categoryIds);
                }

                if (!empty($tagIds)) {
                    $query->orWhereHas('tags', function ($tagQuery) use ($tagIds) {
                        $tagQuery->whereIn('tags.id', $tagIds);
                    });
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(function (News $news) {
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'thumbnail' => $news->thumbnail,
                    'published_at' => optional($news->published_at)->toISOString(),
                    'is_premium' => (bool) $news->is_premium,
                    'category' => $news->category ? [
                        'id' => $news->category->id,
                        'name' => $news->category->name,
                        'slug' => $news->category->slug,
                    ] : null,
                    'tags' => $news->tags->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ])->values()->all(),
                    'author' => $news->user ? [
                        'id' => $news->user->id,
                        'name' => $news->user->name,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'frequency' => $frequency,
            'generated_at' => now()->toISOString(),
            'window_start' => $windowStart->toISOString(),
            'articles' => $articles,
        ]);
    }

    public function markRead(string $notificationId): JsonResponse
    {
        $user = $this->currentUser();
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        $user = $this->currentUser();
        $cutoff = now();
        $user->unreadNotifications()
            ->where('created_at', '<=', $cutoff)
            ->update(['read_at' => $cutoff]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    private function resolveDigestWindowStart(string $frequency): Carbon
    {
        return $frequency === 'daily'
            ? now()->subDay()
            : now()->subWeek();
    }
}
